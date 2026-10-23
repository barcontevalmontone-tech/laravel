<?php

use App\Models\Articolo;
use App\Models\Categoria;
use App\Models\Fornitore;
use App\Services\GestoreMagazzino;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Web magazzino', function () {
    it('shows the unified dashboard on the home page when authenticated', function () {
        $this->get('/')->assertOk()->assertSee('Dashboard operativa');
    });

    it('redirects guests away from the home page', function () {
        $this->app['auth']->forgetGuards();

        $this->get('/')->assertRedirect(route('login'));
    });

    it('shows the dashboard with kpis and reorder alerts', function () {
        Articolo::factory()->create([
            'codice' => 'ART-001',
            'nome' => 'Espresso decaffeinato',
            'giacenza' => 0,
            'giacenza_minima' => 2,
            'giacenza_massima' => 10,
            'costo' => 5,
        ]);

        $this->get('/magazzino')
            ->assertOk()
            ->assertSee('Dashboard magazzino', false)
            ->assertSee('Espresso decaffeinato')
            ->assertSee('Avvisi di riordino', false);
    });

    it('lists articles and supports search', function () {
        Articolo::factory()->create(['codice' => 'ART-COCA', 'nome' => 'Coca Cola 33cl']);
        Articolo::factory()->create(['codice' => 'ART-FANTA', 'nome' => 'Fanta 33cl']);

        $this->get('/magazzino/articoli')
            ->assertOk()
            ->assertSee('Coca Cola 33cl')
            ->assertSee('Fanta 33cl');

        $this->get('/magazzino/articoli?search=Coca')
            ->assertOk()
            ->assertSee('Coca Cola 33cl')
            ->assertDontSee('Fanta 33cl');
    });

    it('creates an article from the form', function () {
        $categoria = Categoria::factory()->create();
        $fornitore = Fornitore::factory()->create();

        $response = $this->post('/magazzino/articoli', [
            'codice' => 'ART-WEB',
            'nome' => 'Articolo da web',
            'unita_misura' => 'pz',
            'categoria_id' => $categoria->id,
            'fornitore_id' => $fornitore->id,
            'costo' => 1.2,
            'prezzo_vendita' => 2.5,
            'attivo' => '1',
        ]);

        $articolo = Articolo::where('codice', 'ART-WEB')->first();

        expect($articolo)->not->toBeNull();

        $response->assertRedirect(route('magazzino.articoli.show', $articolo));
    });

    it('rejects invalid article payloads back to the form', function () {
        $this->from('/magazzino/articoli/create')
            ->post('/magazzino/articoli', ['codice' => '', 'nome' => ''])
            ->assertRedirect('/magazzino/articoli/create')
            ->assertSessionHasErrors(['codice', 'nome']);
    });

    it('updates an article', function () {
        $articolo = Articolo::factory()->create(['nome' => 'Prima']);

        $this->put("/magazzino/articoli/{$articolo->id}", [
            'codice' => $articolo->codice,
            'nome' => 'Dopo',
            'attivo' => '1',
        ])->assertRedirect(route('magazzino.articoli.show', $articolo));

        expect($articolo->fresh()->nome)->toBe('Dopo');
    });

    it('registers movements from the article page and blocks negative stock', function () {
        $articolo = Articolo::factory()->create(['giacenza' => 5]);

        // carico ok
        $this->post("/magazzino/articoli/{$articolo->id}/movimenti", [
            'tipo' => 'carico',
            'quantita' => 3,
            'causale' => 'acquisto',
        ])->assertRedirect()->assertSessionHas('status');

        expect((float) $articolo->fresh()->giacenza)->toBe(8.0);

        // scarico eccessivo bloccato
        $this->post("/magazzino/articoli/{$articolo->id}/movimenti", [
            'tipo' => 'scarico',
            'quantita' => 100,
            'causale' => 'vendita',
        ])->assertSessionHas('error');

        expect((float) $articolo->fresh()->giacenza)->toBe(8.0);
    });

    it('shows the article detail page with movement history', function () {
        $articolo = Articolo::factory()->create(['nome' => 'Cornetto al miele', 'barcode' => null]);
        (new GestoreMagazzino)->carico($articolo, 12, 'acquisto');

        $this->get("/magazzino/articoli/{$articolo->id}")
            ->assertOk()
            ->assertSee('Cornetto al miele')
            ->assertSee('Storico movimenti', false)
            ->assertSee('Acquisto da fornitore');
    });

    it('protects articles with movements from deletion in the web ui', function () {
        $articolo = Articolo::factory()->create();
        (new GestoreMagazzino)->carico($articolo, 1, 'acquisto');

        $this->delete("/magazzino/articoli/{$articolo->id}")->assertSessionHas('error');

        expect(Articolo::find($articolo->id))->not->toBeNull();
    });

    it('deletes an article without movements', function () {
        $articolo = Articolo::factory()->create();

        $this->delete("/magazzino/articoli/{$articolo->id}")
            ->assertRedirect(route('magazzino.articoli.index'));

        expect(Articolo::find($articolo->id))->toBeNull();
    });
});
