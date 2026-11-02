<?php

namespace Database\Factories;

use App\Models\FuelDelivery;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelDelivery>
 */
class FuelDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $volume = fake()->randomFloat(2, 5000, 20000);
        $costPerUnit = fake()->randomFloat(2, 1, 2.5);

        return [
            'tank_id' => Tank::factory(),
            'volume' => $volume,
            'cost_per_unit' => $costPerUnit,
            'total_cost' => round($volume * $costPerUnit, 2),
            'supplier' => fake()->company(),
            'delivered_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => null,
        ];
    }
}
