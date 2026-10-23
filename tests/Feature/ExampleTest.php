<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La rotta principale mostra la dashboard operativa unificata agli
     * utenti autenticati (admin di default nel setUp) e reindirizza gli
     * ospiti alla pagina di login.
     */
    public function test_the_application_shows_the_dashboard_to_authenticated_users(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('Dashboard operativa');
    }

    public function test_the_application_redirects_guests_to_the_login_page(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
