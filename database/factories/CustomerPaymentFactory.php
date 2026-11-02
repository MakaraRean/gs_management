<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerPayment>
 */
class CustomerPaymentFactory extends Factory
{
    /**
     * Stamp the payment with its station's currency unless a test sets one.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (CustomerPayment $payment): void {
            $payment->currency_id ??= CurrencyFactory::idForStation($payment->station_id);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'station_id' => Station::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'payment_method' => 'cash',
            'notes' => null,
            'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
