<?php

namespace Database\Factories;

use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\Movimento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movimento>
 */
class MovimentoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Movimento>
     */
    protected $model = Movimento::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $giacenzaPrima = fake()->randomFloat(3, 0, 100);
        $quantita = fake()->randomFloat(3, 0.5, 20);

        return [
            'articolo_id' => Articolo::factory(),
            'tipo' => fake()->randomElement(TipoMovimento::cases()),
            'quantita' => $quantita,
            'giacenza_prima' => $giacenzaPrima,
            'giacenza_dopo' => $giacenzaPrima + $quantita,
            'causale' => fake()->randomElement(['acquisto', 'vendita', 'inventario', null]),
            'note' => null,
        ];
    }
}
