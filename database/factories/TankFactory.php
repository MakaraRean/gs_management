<?php

namespace Database\Factories;

use App\Models\FuelType;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tank>
 */
class TankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $capacity = fake()->randomElement([20000, 30000, 40000]);

        return [
            'name' => 'Tank '.fake()->unique()->randomLetter(),
            'fuel_type_id' => FuelType::factory(),
            'capacity' => $capacity,
            'current_volume' => fake()->randomFloat(2, $capacity * 0.1, $capacity),
        ];
    }
}
