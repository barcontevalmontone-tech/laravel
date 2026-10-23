<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La rotta principale reindirizza alla dashboard del magazzino.
     */
    public function test_the_application_redirects_to_the_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('magazzino.dashboard'));
    }
}
