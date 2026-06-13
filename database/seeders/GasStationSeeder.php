<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\CashFlow;
use App\Models\FuelDelivery;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Sale;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GasStationSeeder extends Seeder
{
    /**
     * Seed a realistic single-station dataset.
     */
    public function run(): void
    {
        $user = User::query()->first();

        $business = Business::create([
            'name' => 'Demo Petroleum',
            'phone' => '012 345 678',
            'email' => 'demo@example.com',
            'address' => 'Phnom Penh',
        ]);
        $business->user()->associate($user)->save();

        $station = Station::create([
            'business_id' => $business->id,
            'name' => 'Demo Petroleum — Main Station',
            'phone' => $business->phone,
            'email' => $business->email,
            'address' => $business->address,
        ]);

        $fuelTypes = collect([
            ['name' => 'Regular 92', 'unit_price' => 1.25, 'color' => '#16a34a'],
            ['name' => 'Super 95', 'unit_price' => 1.45, 'color' => '#2563eb'],
            ['name' => 'Diesel', 'unit_price' => 1.35, 'color' => '#f59e0b'],
        ])->map(fn (array $attributes): FuelType => FuelType::create([
            ...$attributes,
            'unit' => 'L',
            'station_id' => $station->id,
        ]));

        // One tank + two pumps per fuel type.
        $pumps = collect();

        foreach ($fuelTypes as $index => $fuelType) {
            $capacity = 30000;
            $tank = Tank::create([
                'station_id' => $station->id,
                'name' => 'Tank '.chr(65 + $index),
                'fuel_type_id' => $fuelType->id,
                'capacity' => $capacity,
                'current_volume' => $capacity * 0.7,
            ]);

            for ($p = 1; $p <= 2; $p++) {
                $pumps->push(Pump::create([
                    'name' => "Pump {$tank->name}-{$p}",
                    'tank_id' => $tank->id,
                    'status' => 'active',
                ]));
            }

            // A couple of historical deliveries per tank.
            FuelDelivery::factory()->count(2)->create([
                'tank_id' => $tank->id,
                'cost_per_unit' => round((float) $fuelType->unit_price * 0.8, 2),
            ]);
        }

        // Generate 30 days of sales across pumps.
        $pumps = Pump::with('tank.fuelType')->get();

        for ($day = 29; $day >= 0; $day--) {
            $date = Carbon::today()->subDays($day);
            $salesForDay = random_int(8, 25);

            for ($s = 0; $s < $salesForDay; $s++) {
                /** @var Pump $pump */
                $pump = $pumps->random();
                $fuelType = $pump->tank->fuelType;
                $volume = round(mt_rand(500, 8000) / 100, 2);

                Sale::create([
                    'station_id' => $station->id,
                    'pump_id' => $pump->id,
                    'fuel_type_id' => $fuelType->id,
                    'user_id' => $user?->id,
                    'volume' => $volume,
                    'unit_price' => $fuelType->unit_price,
                    'total_amount' => round($volume * (float) $fuelType->unit_price, 2),
                    'payment_method' => random_int(1, 10) > 4 ? 'cash' : 'card',
                    'sold_at' => $date->copy()->addHours(random_int(6, 22))->addMinutes(random_int(0, 59)),
                ]);
            }
        }

        // A few manual cash-flow entries (expenses / misc income).
        CashFlow::factory()->count(8)->create([
            'user_id' => $user?->id,
            'station_id' => $station->id,
        ]);
    }
}
