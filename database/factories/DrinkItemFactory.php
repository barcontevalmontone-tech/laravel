<?php

namespace Database\Factories;

use App\Enum\DrinkPackageType;
use App\Models\DrinkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DrinkItem>
 */
class DrinkItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cl' => fake()->numberBetween(5, 100),
            'package_type' => fake()->randomElement(DrinkPackageType::cases()),
            'barcode' => fake()->numerify('#############'),
        ];
    }
}
