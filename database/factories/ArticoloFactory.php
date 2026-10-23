<?php

namespace Database\Factories;

use App\Models\Articolo;
use App\Models\Categoria;
use App\Models\Fornitore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Articolo>
 */
class ArticoloFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Articolo>
     */
    protected $model = Articolo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codice' => fake()->unique()->bothify('ART-####??'),
            'barcode' => fake()->unique()->ean13(),
            'nome' => fake()->words(3, true),
            'unita_misura' => 'pz',
            'categoria_id' => Categoria::factory(),
            'fornitore_id' => Fornitore::factory(),
            'costo' => fake()->randomFloat(2, 0.5, 50),
            'prezzo_vendita' => fake()->randomFloat(2, 1, 100),
            'giacenza' => fake()->randomFloat(3, 0, 100),
            'giacenza_minima' => fake()->randomFloat(3, 0, 10),
            'giacenza_massima' => null,
            'attivo' => true,
            'note' => null,
        ];
    }
}
