<?php

namespace Database\Factories;

use App\Models\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelType>
 */
class FuelTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Regular 92', 'Super 95', 'Diesel', 'Premium 98']),
            'unit_price' => fake()->randomFloat(2, 1, 3),
            'unit' => 'L',
            'color' => fake()->hexColor(),
        ];
    }
}
