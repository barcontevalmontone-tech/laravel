<?php

use App\Models\Articolo;
use App\Models\Categoria;
use App\Models\Fornitore;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('the core migrations create the expected tables', function () {
    expect(Schema::hasTable('categorie'))->toBeTrue()
        ->and(Schema::hasTable('fornitori'))->toBeTrue()
        ->and(Schema::hasTable('articoli'))->toBeTrue();
});

describe('Categoria', function () {
    it('can be created with a name', function () {
        $categoria = Categoria::factory()->create(['nome' => 'Bevande']);

        expect($categoria->fresh()->nome)->toBe('Bevande');
    });

    it('supports parent and child relationships', function () {
        $padre = Categoria::factory()->create();
        $figlia = Categoria::factory()->create(['parent_id' => $padre->id]);

        expect($figlia->padre->id)->toBe($padre->id)
            ->and($padre->figlie->pluck('id'))->toContain($figlia->id);
    });

    it('nullifies the parent reference when the parent is deleted', function () {
        $padre = Categoria::factory()->create();
        $figlia = Categoria::factory()->create(['parent_id' => $padre->id]);

        $padre->delete();

        expect($figlia->fresh()->parent_id)->toBeNull();
    });

    it('rejects duplicate names within the same level', function () {
        $padre = Categoria::factory()->create();

        Categoria::factory()->create(['nome' => 'Snack', 'parent_id' => $padre->id]);

        expect(fn () => Categoria::factory()->create(['nome' => 'Snack', 'parent_id' => $padre->id]))
            ->toThrow(QueryException::class);
    });
});

describe('Fornitore', function () {
    it('can be created with its core fields', function () {
        $fornitore = Fornitore::factory()->create([
            'ragione_sociale' => 'Heineken Italia',
            'partita_iva' => 'IT00891290123',
        ]);

        expect($fornitore->fresh())
            ->ragione_sociale->toBe('Heineken Italia')
            ->partita_iva->toBe('IT00891290123');
    });

    it('requires a unique vat number', function () {
        Fornitore::factory()->create(['partita_iva' => 'IT11111111111']);

        expect(fn () => Fornitore::factory()->create(['partita_iva' => 'IT11111111111']))
            ->toThrow(QueryException::class);
    });
});

describe('Articolo', function () {
    it('belongs to a category and a vendor', function () {
        $articolo = Articolo::factory()->create();

        expect($articolo->categoria)->toBeInstanceOf(Categoria::class)
            ->and($articolo->fornitore)->toBeInstanceOf(Fornitore::class);
    });

    it('requires a unique code', function () {
        $codice = Articolo::factory()->create()->codice;

        expect(fn () => Articolo::factory()->create(['codice' => $codice]))
            ->toThrow(QueryException::class);
    });

    it('applies model defaults when the fields are omitted', function () {
        $articolo = new Articolo(['codice' => 'A1', 'nome' => 'Coca Cola']);

        expect($articolo->attivo)->toBeTrue()
            ->and($articolo->unita_misura)->toBe('pz');
    });

    it('persists the database default for active articles', function () {
        $categoria = Categoria::factory()->create();
        $fornitore = Fornitore::factory()->create();

        $articolo = DB::table('articoli')->insertGetId([
            'codice' => 'ART-DB-1',
            'nome' => 'Acqua Naturale',
            'categoria_id' => $categoria->id,
            'fornitore_id' => $fornitore->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        expect((bool) DB::table('articoli')->find($articolo)->attivo)->toBeTrue();
    });

    it('filters only active articles with the attivi scope', function () {
        $attivo = Articolo::factory()->create(['attivo' => true]);
        $disattivo = Articolo::factory()->create(['attivo' => false]);

        expect(Articolo::attivi()->pluck('id'))
            ->toContain($attivo->id)
            ->not->toContain($disattivo->id);
    });

    it('detects stock below the minimum threshold', function () {
        $sottoSoglia = Articolo::factory()->create(['giacenza' => 2, 'giacenza_minima' => 5]);
        $sopraSoglia = Articolo::factory()->create(['giacenza' => 10, 'giacenza_minima' => 5]);

        expect($sottoSoglia->sottoSogliaMinima())->toBeTrue()
            ->and($sopraSoglia->sottoSogliaMinima())->toBeFalse();
    });
});
