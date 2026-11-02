<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Station;
use App\Models\Tank;
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
            'code' => strtoupper(fake()->unique()->lexify('???')),
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

    /**
     * The currency a new record of this station is stamped with: its own, else its business's, else USD.
     */
    public static function idForStation(?int $stationId): int
    {
        $station = $stationId
            ? Station::query()->withInactive()->with(['currency', 'business.currency'])->find($stationId)
            : null;

        return ($station?->effective_currency ?? self::seeded('USD'))->id;
    }

    /**
     * The currency a new delivery into this tank is stamped with (that of the tank's station).
     */
    public static function idForTank(?int $tankId): int
    {
        return self::idForStation($tankId ? Tank::query()->withInactive()->find($tankId)?->station_id : null);
    }
}
