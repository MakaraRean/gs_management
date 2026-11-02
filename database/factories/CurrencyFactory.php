<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'X'.strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->word(),
            'name_km' => fake()->word(),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥']),
            'round_digits' => 2,
            'is_active' => true,
        ];
    }

    public function usd(): static
    {
        return $this->state(fn (): array => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'name_km' => 'ដុល្លារអាមេរិក',
            'symbol' => '$',
            'round_digits' => 2,
        ]);
    }

    public function khr(): static
    {
        return $this->state(fn (): array => [
            'code' => 'KHR',
            'name' => 'Cambodian Riel',
            'name_km' => 'រៀលកម្ពុជា',
            'symbol' => '៛',
            'round_digits' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * The seeded currency with this code (the currencies migration ships USD, KHR, THB and VND).
     */
    public static function seeded(string $code): Currency
    {
        return Currency::query()->where('code', $code)->firstOrFail();
    }
}
