<?php

use App\Enums\TipoMovimento;
use App\Exceptions\GiacenzaInsufficiente;
use App\Models\Articolo;
use App\Models\Movimento;
use App\Services\GestoreMagazzino;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gestore = new GestoreMagazzino;
});

test('the movements migration creates the expected table', function () {
    expect(Schema::hasTable('movimenti'))->toBeTrue();
});

describe('TipoMovimento enum', function () {
    it('maps every case to its sign', function () {
        expect(TipoMovimento::Carico->segno())->toBe(1)
            ->and(TipoMovimento::Scarico->segno())->toBe(-1)
            ->and(TipoMovimento::Rettifica->segno())->toBe(0);
    });

    it('provides an italian label for every case', function () {
        expect(TipoMovimento::Carico->label())->toBe('Carico')
            ->and(TipoMovimento::Scarico->label())->toBe('Scarico')
            ->and(TipoMovimento::Rettifica->label())->toBe('Rettifica');
    });
});

describe('GestoreMagazzino', function () {
    it('increases the stock on a carico and snapshots before/after values', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 10]);

        $movimento = $this->gestore->carico($articolo, 5, 'acquisto');

        expect($movimento->fresh())
            ->tipo->toBe(TipoMovimento::Carico)
            ->quantita->toBe('5.000')
            ->giacenza_prima->toBe('10.000')
            ->giacenza_dopo->toBe('15.000')
            ->causale->toBe('acquisto')
            ->articolo->id->toBe($articolo->id);

        expect((float) $articolo->fresh()->giacenza)->toBe(15.0);
    });

    it('decreases the stock on a scarico', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 10]);

        $movimento = $this->gestore->scarico($articolo, 4, 'vendita');

        expect($movimento->fresh())
            ->tipo->toBe(TipoMovimento::Scarico)
            ->giacenza_prima->toBe('10.000')
            ->giacenza_dopo->toBe('6.000');

        expect((float) $articolo->fresh()->giacenza)->toBe(6.0);
    });

    it('sets an absolute value on a rettifica', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 10]);

        $movimento = $this->gestore->rettifica($articolo, 3, 'inventario');

        expect($movimento->fresh())
            ->tipo->toBe(TipoMovimento::Rettifica)
            ->giacenza_prima->toBe('10.000')
            ->giacenza_dopo->toBe('3.000');

        expect((float) $articolo->fresh()->giacenza)->toBe(3.0);
    });

    it('refuses a scarico that would bring the stock below zero', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 2]);

        expect(fn () => $this->gestore->scarico($articolo, 5))
            ->toThrow(GiacenzaInsufficiente::class);

        expect((float) $articolo->fresh()->giacenza)->toBe(2.0);
    });

    it('rolls back the movement row when the scarico is rejected', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 2]);

        try {
            $this->gestore->scarico($articolo, 5);
        } catch (GiacenzaInsufficiente) {
            // expected
        }

        expect(Movimento::count())->toBe(0);
    });

    it('allows a scarico that brings the stock exactly to zero', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 7]);

        $this->gestore->scarico($articolo, 7);

        expect((float) $articolo->fresh()->giacenza)->toBe(0.0);
    });

    it('rejects negative quantities', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 10]);

        expect(fn () => $this->gestore->registra($articolo, TipoMovimento::Carico, -3))
            ->toThrow(InvalidArgumentException::class);
    });

    it('keeps stock consistent across sequential movements', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 0]);

        $this->gestore->carico($articolo, 10);
        $this->gestore->scarico($articolo, 3);
        $this->gestore->rettifica($articolo, 20);
        $this->gestore->scarico($articolo, 8);

        expect((float) $articolo->fresh()->giacenza)->toBe(12.0);
        expect(Movimento::orderBy('id')->pluck('giacenza_dopo')->all())
            ->toBe(['10.000', '7.000', '20.000', '12.000']);
    });

    it('exposes the article movements ordered from newest to oldest', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 0]);

        $primo = $this->gestore->carico($articolo, 10);
        $secondo = $this->gestore->scarico($articolo, 2);

        expect($articolo->fresh()->movimenti->pluck('id')->all())
            ->toBe([$secondo->id, $primo->id]);
    });

    it('requires an existing article', function () {
        $articolo = Articolo::factory()->create();

        expect(fn () => Movimento::factory()->create(['articolo_id' => 999999]))
            ->toThrow(QueryException::class);
    });

    it('persists the enum type as its scalar value', function () {
        $movimento = Movimento::factory()->create(['tipo' => TipoMovimento::Carico]);

        expect(DB::table('movimenti')->where('id', $movimento->id)->value('tipo'))
            ->toBe('carico');
    });
});
