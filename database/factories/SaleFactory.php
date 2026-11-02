<?php

namespace Database\Factories;

use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Stamp the sale with its station's currency unless a test sets one.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Sale $sale): void {
            $sale->currency_id ??= CurrencyFactory::idForStation($sale->station_id);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $volume = fake()->randomFloat(2, 5, 80);
        $unitPrice = fake()->randomFloat(2, 1, 3);

        return [
            'pump_id' => Pump::factory(),
            'fuel_type_id' => FuelType::factory(),
            'user_id' => null,
            'volume' => $volume,
            'unit_price' => $unitPrice,
            'total_amount' => round($volume * $unitPrice, 2),
            'payment_method' => fake()->randomElement(['cash', 'card']),
            'sold_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => null,
        ];
    }
}
