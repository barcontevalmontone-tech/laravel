<?php

use App\Models\ProdottoBar;
use App\Models\RigaScontrino;
use App\Models\Scontrino;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crea un prodotto bar semplice con giacenza data.
 */
function prodottoSemplice(string $nome, float $giacenza, float $prezzo = 1.0): ProdottoBar
{
    return ProdottoBar::create([
        'nome' => $nome,
        'categoria' => 'Bevande fredde',
        'prezzo_acquisto' => 0.5,
        'prezzo_vendita' => $prezzo,
        'giacenza_attuale' => $giacenza,
        'soglia_minima' => 0,
        'ha_ricetta' => false,
    ]);
}

it('conferma una vendita di prodotti semplici scaricando le giacenze', function (): void {
    $caffe = prodottoSemplice('Caffè espresso', 100.0, 1.2);
    $acqua = prodottoSemplice('Acqua 50cl', 24.0, 1.0);

    $response = $this->postJson('/api/pos/confirm', [
        'articoli' => [
            ['prodotto_bar_id' => $caffe->id, 'quantita' => 2],
            ['prodotto_bar_id' => $acqua->id, 'quantita' => 1],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('scontrino.numero_scontrino', 1)
        ->assertJsonPath('scontrino.totale', '3.40');

    expect($caffe->refresh()->giacenza_attuale)->toEqual(98.0)
        ->and($acqua->refresh()->giacenza_attuale)->toEqual(23.0)
        ->and(Scontrino::count())->toBe(1)
        ->and(RigaScontrino::count())->toBe(2);
});

it('assegna numeri di scontrino progressivi', function (): void {
    $prodotto = prodottoSemplice('Birra 33cl', 50.0, 3.5);

    $this->postJson('/api/pos/confirm', [
        'articoli' => [['prodotto_bar_id' => $prodotto->id, 'quantita' => 1]],
    ])->assertCreated()->assertJsonPath('scontrino.numero_scontrino', 1);

    $this->postJson('/api/pos/confirm', [
        'articoli' => [['prodotto_bar_id' => $prodotto->id, 'quantita' => 1]],
    ])->assertCreated()->assertJsonPath('scontrino.numero_scontrino', 2);

    expect($prodotto->refresh()->giacenza_attuale)->toEqual(48.0);
});

it('rifiuta la vendita se la giacenza è insufficiente e non crea scontrini', function (): void {
    $prodotto = prodottoSemplice('Panino farcito', 3.0, 3.0);

    $response = $this->postJson('/api/pos/confirm', [
        'articoli' => [['prodotto_bar_id' => $prodotto->id, 'quantita' => 5]],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errori.0.motivo', 'Giacenza insufficiente.');

    expect($prodotto->refresh()->giacenza_attuale)->toEqual(3.0)
        ->and(Scontrino::count())->toBe(0);
});

it('scarica gli ingredienti a ricetta sui prodotti magazzino corrispondenti', function (): void {
    // Ingredienti con giacenza propria in prodotti_bar.
    $caffeMacinato = prodottoSemplice('Caffè macinato', 500.0, 0.0);
    $latte = prodottoSemplice('Latte intero', 10.0, 0.0);

    $cappuccino = ProdottoBar::create([
        'nome' => 'Cappuccino',
        'categoria' => 'Bevande calde',
        'prezzo_acquisto' => 0.45,
        'prezzo_vendita' => 1.6,
        'ha_ricetta' => true,
    ]);
    $cappuccino->ricette()->createMany([
        ['ingrediente_nome' => 'Caffè macinato', 'quantita_uso' => 7.0, 'unita_misura' => 'g'],
        ['ingrediente_nome' => 'Latte intero', 'quantita_uso' => 0.12, 'unita_misura' => 'l'],
        ['ingrediente_nome' => 'Zucchero', 'quantita_uso' => 5.0, 'unita_misura' => 'g'], // sfuso: non in magazzino
    ]);

    $response = $this->postJson('/api/pos/confirm', [
        'articoli' => [['prodotto_bar_id' => $cappuccino->id, 'quantita' => 2]],
    ]);

    $response->assertCreated()
        ->assertJsonPath('scontrino.righe.0.ingredienti_consumati.0.quantita', 14)
        ->assertJsonPath('scontrino.righe.0.ingredienti_consumati.0.scaricato_da_magazzino', true)
        ->assertJsonPath('scontrino.righe.0.ingredienti_consumati.2.scaricato_da_magazzino', false);

    // 2 cappuccini: 14 g di caffè e 0.24 l di latte scaricati, lo zucchero (sfuso) no.
    expect($caffeMacinato->refresh()->giacenza_attuale)->toEqual(486.0)
        ->and($latte->refresh()->giacenza_attuale)->toEqual(9.76)
        ->and((float) $cappuccino->refresh()->giacenza_attuale)->toEqual(0.0);
});

it('blocca la cessione di troppi cappuccini per mancanza di ingredienti', function (): void {
    $latte = prodottoSemplice('Latte intero', 0.1, 0.0);

    $cappuccino = ProdottoBar::create([
        'nome' => 'Cappuccino',
        'categoria' => 'Bevande calde',
        'prezzo_acquisto' => 0.45,
        'prezzo_vendita' => 1.6,
        'ha_ricetta' => true,
    ]);
    $cappuccino->ricette()->create([
        'ingrediente_nome' => 'Latte intero',
        'quantita_uso' => 0.12,
        'unita_misura' => 'l',
    ]);

    $this->postJson('/api/pos/confirm', [
        'articoli' => [['prodotto_bar_id' => $cappuccino->id, 'quantita' => 1]],
    ])
        ->assertStatus(422)
        ->assertJsonPath('errori.0.motivo', 'Giacenza ingrediente insufficiente.');

    expect(Scontrino::count())->toBe(0)
        ->and($latte->refresh()->giacenza_attuale)->toEqual(0.1);
});

it('non somma quantità righe duplicate oltre la giacenza', function (): void {
    $prodotto = prodottoSemplice('Acqua 50cl', 5.0, 1.0);

    // Due righe da 3 sullo stesso prodotto con giacenza 5: il totale richiesto è 6.
    $this->postJson('/api/pos/confirm', [
        'articoli' => [
            ['prodotto_bar_id' => $prodotto->id, 'quantita' => 3],
            ['prodotto_bar_id' => $prodotto->id, 'quantita' => 3],
        ],
    ])->assertStatus(422);

    expect($prodotto->refresh()->giacenza_attuale)->toEqual(5.0)
        ->and(Scontrino::count())->toBe(0);
});
