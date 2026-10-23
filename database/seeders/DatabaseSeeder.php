<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        // Demo del magazzino (disattivabile con SEED_DEMO=false).
        if (filter_var(env('SEED_DEMO', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoMagazzinoSeeder::class);
        }
    }
}
