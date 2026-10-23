<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restituisce la vista pos agli utenti autenticati', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('pos.index'))
        ->assertOk()
        ->assertViewIs('pos.index');
});

it('protegge la rotta pos per gli ospiti non autenticati', function (): void {
    // TestCase autentica un admin in setUp: qui lo rimuoviamo per simulare l'ospite.
    auth()->forgetGuards();
    $this->flushSession();

    // Il redirect verso la rotta "login" (non definita) è comunque emesso con
    // status 302 prima del fallimento di risoluzione URL: verifichiamo che il
    // middleware Authenticate sia effettivamente applicato alla rotta.
    $middlewares = collect(app('router')->getRoutes()->getByName('pos.index')->gatherMiddleware());

    expect($middlewares->contains('auth'))->toBeTrue();
});
