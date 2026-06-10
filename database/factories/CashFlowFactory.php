<?php

namespace Database\Factories;

use App\Models\CashFlow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashFlow>
 */
class CashFlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $direction = fake()->randomElement(['in', 'out']);

        return [
            'user_id' => null,
            'direction' => $direction,
            'category' => $direction === 'in'
                ? fake()->randomElement(['other_income', 'deposit'])
                : fake()->randomElement(['expense', 'withdrawal']),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'description' => fake()->sentence(3),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
