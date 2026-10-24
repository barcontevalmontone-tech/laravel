<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Eseguito prima di ogni test: autentica un utente admin, così le rotte
     * protette (auth:sanctum per l'API e auth per il web) restano utilizzabili
     * nei test funzionali. Le singole suite possono sovrascrivere l'utente
     * chiamando nuovamente actingAs() nel test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    /**
     * Autentica un utente amministratore su tutti i guard rilevanti.
     */
    protected function actingAsAdmin(?User $utente = null): User
    {
        $utente ??= User::factory()->create(['ruolo' => 'admin']);

        // Guard API (token-less in test) e guard web/sessione.
        \Laravel\Sanctum\Sanctum::actingAs($utente);
        $this->be($utente, 'web');

        return $utente;
    }

    /**
     * Autentica un utente con ruolo operatore (no privilegi admin).
     */
    protected function actingAsOperatore(?User $utente = null): User
    {
        $utente ??= User::factory()->create(['ruolo' => 'operatore']);

        \Laravel\Sanctum\Sanctum::actingAs($utente);
        $this->be($utente, 'web');

        return $utente;
    }
}
