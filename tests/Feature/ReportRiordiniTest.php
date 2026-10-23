<?php

use App\Models\Articolo;
use App\Services\ReportRiordini;
use App\Support\Riordino;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->report = new ReportRiordini;
});

test('restituisce solo gli articoli attivi con giacenza sotto la soglia minima', function () {
    Articolo::factory()->create(['nome' => 'Sotto soglia', 'giacenza' => 2, 'giacenza_minima' => 5]);
    Articolo::factory()->create(['nome' => 'Esaurito', 'giacenza' => 0, 'giacenza_minima' => 5]);
    Articolo::factory()->create(['nome' => 'A livello soglia', 'giacenza' => 5, 'giacenza_minima' => 5]);
    Articolo::factory()->create(['nome' => 'Sopra soglia', 'giacenza' => 10, 'giacenza_minima' => 5]);
    Articolo::factory()->create(['nome' => 'Senza soglia', 'giacenza' => 0, 'giacenza_minima' => 0]);
    Articolo::factory()->create(['nome' => 'Disattivo', 'giacenza' => 1, 'giacenza_minima' => 5, 'attivo' => false]);

    $nomi = $this->report->articoliDaOrdinare()->map(fn (Riordino $r) => $r->articolo->nome);

    expect($nomi)->toHaveCount(3)
        ->and($nomi->all())->toBe(['Esaurito', 'Sotto soglia', 'A livello soglia']);
});

test('esauriti precedono gli altri e l ordinamento interno e per scorta crescente', function () {
    Articolo::factory()->create(['nome' => 'Poca scorta', 'giacenza' => 1, 'giacenza_minima' => 10]);
    Articolo::factory()->create(['nome' => 'Zero', 'giacenza' => 0, 'giacenza_minima' => 10]);
    Articolo::factory()->create(['nome' => 'Meno poca', 'giacenza' => 3, 'giacenza_minima' => 10]);

    $lista = $this->report->articoliDaOrdinare();

    expect($lista->map(fn (Riordino $r) => $r->articolo->nome)->all())
        ->toBe(['Zero', 'Poca scorta', 'Meno poca'])
        ->and($lista->first()->esaurito())->toBeTrue()
        ->and($lista->last()->esaurito())->toBeFalse();
});

test('quantita da ordinare raggiunge la giacenza massima', function () {
    $articolo = Articolo::factory()->create([
        'giacenza' => 4,
        'giacenza_minima' => 10,
        'giacenza_massima' => 30,
    ]);

    $riordino = $this->report->articoliDaOrdinare()->firstWhere('articolo.id', $articolo->id);

    expect($riordino)->not->toBeNull()
        ->and($riordino->quantitaDaOrdinare)->toBe(26.0);
});

test('senza giacenza massima usa il doppio della soglia minima', function () {
    $articolo = Articolo::factory()->create([
        'giacenza' => 3,
        'giacenza_minima' => 10,
        'giacenza_massima' => null,
    ]);

    $riordino = $this->report->articoliDaOrdinare()->firstWhere('articolo.id', $articolo->id);

    expect($riordino->quantitaDaOrdinare)->toBe(17.0);
});

test('giacenza massima non superiore alla minima viene ignorata', function () {
    $articolo = Articolo::factory()->create([
        'giacenza' => 2,
        'giacenza_minima' => 10,
        'giacenza_massima' => 8,
    ]);

    $riordino = $this->report->articoliDaOrdinare()->firstWhere('articolo.id', $articolo->id);

    expect($riordino->quantitaDaOrdinare)->toBe(18.0);
});

test('il riepilogo somma conteggio e valore al costo', function () {
    Articolo::factory()->create(['giacenza' => 0, 'giacenza_minima' => 10, 'giacenza_massima' => 20, 'costo' => 2.5]); // ordina 20 => 50
    Articolo::factory()->create(['giacenza' => 5, 'giacenza_minima' => 5, 'giacenza_massima' => 15, 'costo' => 1]);     // ordina 10 => 10
    Articolo::factory()->create(['giacenza' => 99, 'giacenza_minima' => 5, 'costo' => 100]);                          // fuori lista

    expect($this->report->riepilogo())->toBe(['conteggio' => 2, 'valore_costo' => 60.0]);
});

test('lista vuota quando nessun articolo e in riordino', function () {
    Articolo::factory()->create(['giacenza' => 50, 'giacenza_minima' => 5]);

    expect($this->report->articoliDaOrdinare())->toBeEmpty()
        ->and($this->report->riepilogo())->toBe(['conteggio' => 0, 'valore_costo' => 0.0]);
});

test('carica le relazioni categoria e fornitore senza n+1', function () {
    $articolo = Articolo::factory()->create(['giacenza' => 0, 'giacenza_minima' => 5]);

    $riordino = $this->report->articoliDaOrdinare()->first();

    expect($riordino->articolo->relationLoaded('categoria'))->toBeTrue()
        ->and($riordino->articolo->relationLoaded('fornitore'))->toBeTrue()
        ->and($riordino->sogliaMinima)->toBe(5.0)
        ->and($riordino->giacenzaAttuale)->toBe(0.0);
});
