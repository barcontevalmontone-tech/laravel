<?php

use App\Models\Articolo;
use App\Models\Categoria;
use App\Models\Fornitore;
use App\Services\GestoreMagazzino;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * Crea un articolo di test con anagrafiche collegate.
 */
function articoloDiTest(array $attributi = []): Articolo
{
    return Articolo::factory()->create($attributi);
}

describe('API articoli', function () {
    it('lists articles paginated', function () {
        articoloDiTest(['nome' => 'Coca Cola 33cl']);
        articoloDiTest(['nome' => 'Fanta 33cl']);

        $response = $this->getJson('/api/articoli');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => ['*' => ['id', 'codice', 'nome']]]);
    });

    it('searches articles by name, code and barcode', function () {
        articoloDiTest(['nome' => 'Espresso', 'codice' => 'ART-0001', 'barcode' => null]);
        articoloDiTest(['nome' => 'Tè', 'codice' => 'ART-0002', 'barcode' => '8000123456789']);

        $this->getJson('/api/articoli?search=Espresso')->assertJsonCount(1, 'data');
        $this->getJson('/api/articoli?search=ART-0002')->assertJsonCount(1, 'data');
        $this->getJson('/api/articoli?search=8000123456789')->assertJsonCount(1, 'data');
        $this->getJson('/api/articoli?search=inesistente')->assertJsonCount(0, 'data');
    });

    it('creates a valid article', function () {
        $categoria = Categoria::factory()->create();
        $fornitore = Fornitore::factory()->create();

        $response = $this->postJson('/api/articoli', [
            'codice' => 'ART-NEW',
            'nome' => 'Nuovo articolo',
            'categoria_id' => $categoria->id,
            'fornitore_id' => $fornitore->id,
            'costo' => 1.5,
            'prezzo_vendita' => 3,
        ]);

        expectArticoloResponse($response)->assertCreated();

        expect(Articolo::where('codice', 'ART-NEW')->exists())->toBeTrue();
    });

    it('rejects duplicated codes with validation errors', function () {
        articoloDiTest(['codice' => 'ART-DUP']);

        $this->postJson('/api/articoli', ['codice' => 'ART-DUP', 'nome' => 'Altro'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codice']);
    });

    it('shows a single article', function () {
        $articolo = articoloDiTest();

        expectArticoloResponse($this->getJson("/api/articoli/{$articolo->id}")->assertOk());
    });

    it('updates an article', function () {
        $articolo = articoloDiTest(['nome' => 'Vecchio nome']);

        $this->putJson("/api/articoli/{$articolo->id}", [
            'codice' => $articolo->codice,
            'nome' => 'Nuovo nome',
        ])->assertOk();

        expect($articolo->fresh()->nome)->toBe('Nuovo nome');
    });

    it('deletes an article without movements', function () {
        $articolo = articoloDiTest();

        $this->deleteJson("/api/articoli/{$articolo->id}")->assertNoContent();

        expect(Articolo::find($articolo->id))->toBeNull();
    });

    it('protects from deletion articles with movements', function () {
        $articolo = articoloDiTest();
        (new GestoreMagazzino)->carico($articolo, 5, 'acquisto');

        $this->deleteJson("/api/articoli/{$articolo->id}")
            ->assertStatus(409);

        expect(Articolo::find($articolo->id))->not->toBeNull();
    });
});

describe('API movimenti', function () {
    it('registers a carico through the API and updates the stock', function () {
        $articolo = articoloDiTest(['giacenza' => 10]);

        $response = $this->postJson("/api/articoli/{$articolo->id}/movimenti", [
            'tipo' => 'carico',
            'quantita' => 5,
            'causale' => 'acquisto',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.tipo', 'carico')
            ->assertJsonPath('data.giacenza_prima', '10.000')
            ->assertJsonPath('data.giacenza_dopo', '15.000');

        expect((float) $articolo->fresh()->giacenza)->toBe(15.0);
    });

    it('returns 422 when a scarico exceeds the stock', function () {
        $articolo = articoloDiTest(['giacenza' => 2]);

        $this->postJson("/api/articoli/{$articolo->id}/movimenti", [
            'tipo' => 'scarico',
            'quantita' => 10,
            'causale' => 'vendita',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        expect((float) $articolo->fresh()->giacenza)->toBe(2.0);
    });

    it('validates movement payload', function () {
        $articolo = articoloDiTest();

        $this->postJson("/api/articoli/{$articolo->id}/movimenti", [
            'tipo' => 'nonoesiste',
            'quantita' => -3,
        ])->assertUnprocessable()->assertJsonValidationErrors(['tipo', 'quantita']);
    });

    it('lists article movements newest first', function () {
        $articolo = articoloDiTest(['giacenza' => 0]);
        $gestore = new GestoreMagazzino;
        $gestore->carico($articolo, 10, 'acquisto');
        $gestore->scarico($articolo, 3, 'vendita');

        $this->getJson("/api/articoli/{$articolo->codice}/movimenti")
            ->assertOk()
            ->assertJsonPath('data.0.causale', 'vendita')
            ->assertJsonPath('data.1.causale', 'acquisto');
    });
});

describe('API riordini', function () {
    it('exposes the reorder list with summary', function () {
        articoloDiTest(['giacenza' => 1, 'giacenza_minima' => 5, 'giacenza_massima' => 20, 'costo' => 2]);
        articoloDiTest(['giacenza' => 50, 'giacenza_minima' => 5]); // sopra soglia, escluso

        $this->getJson('/api/riordini')
            ->assertOk()
            ->assertJsonPath('riepilogo.conteggio', 1)
            ->assertJsonPath('articoli.0.quantita_da_ordinare', 19)
            ->assertJsonPath('articoli.0.esaurito', false);
    });
});

/**
 * Asserts the standard JSON envelope of an article resource response.
 */
function expectArticoloResponse(TestResponse $response): TestResponse
{
    return $response->assertJsonStructure([
        'data' => [
            'id', 'codice', 'barcode', 'nome', 'unita_misura',
            'costo', 'prezzo_vendita', 'giacenza', 'giacenza_minima',
            'giacenza_massima', 'attivo', 'note', 'created_at', 'updated_at',
        ],
    ]);
}
