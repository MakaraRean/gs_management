<?php

namespace Database\Factories;

use App\Models\Pump;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pump>
 */
class PumpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Pump '.fake()->unique()->numberBetween(1, 99),
            'tank_id' => Tank::factory(),
            'status' => 'active',
        ];
    }
}
