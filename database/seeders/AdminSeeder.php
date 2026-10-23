<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea l'amministratore iniziale del magazzino.
 *
 * La password viene letta dall'variabile d'ambiente ADMIN_PASSWORD
 * (default: una password demo da cambiare subito).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@magazzino.test')],
            [
                'name' => env('ADMIN_NAME', 'Amministratore'),
                'password' => env('ADMIN_PASSWORD', 'cambiare-subito'),
                'ruolo' => 'admin',
            ]
        );
    }
}
