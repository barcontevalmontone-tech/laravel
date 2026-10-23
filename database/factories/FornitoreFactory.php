<?php

namespace Database\Factories;

use App\Models\Fornitore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fornitore>
 */
class FornitoreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Fornitore>
     */
    protected $model = Fornitore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ragione_sociale' => fake()->company(),
            'partita_iva' => fake()->unique()->numerify('IT###########'),
            'telefono' => fake()->numerify('+39 0# #######'),
            'email' => fake()->unique()->companyEmail(),
            'note' => null,
        ];
    }
}
