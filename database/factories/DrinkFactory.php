<?php

namespace Database\Factories;

use App\Models\Drink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Drink>
 */
class DrinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'brand' => fake()->company,
            'description' => fake()->paragraph(),
            'alcoholic' => fake()->boolean(),
            'type' => fake()->word,
            'sold_to_minor' => fake()->boolean(),
            'sugar_free' => fake()->boolean(),
            'gluten_free' => fake()->boolean(),
        ];
    }
}
