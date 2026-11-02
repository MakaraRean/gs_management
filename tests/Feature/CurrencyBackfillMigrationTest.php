<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CashFlow;
use App\Models\CustomerPayment;
use App\Models\FuelDelivery;
use App\Models\Sale;
use App\Models\Station;
use App\Models\Tank;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_businesses_start_in_usd_and_their_stations_inherit_it(): void
    {
        $business = Business::factory()->create(['currency_id' => CurrencyFactory::seeded('KHR')->id]);
        $station = Station::factory()->for($business)->create(['currency_id' => CurrencyFactory::seeded('THB')->id]);

        $migration = require database_path('migrations/2026_11_02_041840_add_currency_id_to_businesses_and_stations_tables.php');
        $migration->down();
        $migration->up();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'currency_id' => CurrencyFactory::seeded('USD')->id]);
        $this->assertDatabaseHas('stations', ['id' => $station->id, 'currency_id' => null]);
    }

    public function test_existing_records_take_the_effective_currency_of_their_station(): void
    {
        $khr = CurrencyFactory::seeded('KHR');
        $usd = CurrencyFactory::seeded('USD');
        $business = Business::factory()->create();
        $inheriting = Station::factory()->for($business)->create();
        $overriding = Station::factory()->for($business)->create(['currency_id' => $khr->id]);
        $overridingTank = Tank::factory()->create(['station_id' => $overriding->id]);

        $records = [
            'sales' => [
                Sale::factory()->create(['station_id' => $inheriting->id])->id => $usd->id,
                Sale::factory()->create(['station_id' => $overriding->id])->id => $khr->id,
                // No station: always shown in USD.
                Sale::factory()->create()->id => $usd->id,
            ],
            'cash_flows' => [
                CashFlow::factory()->create(['station_id' => $inheriting->id])->id => $usd->id,
                CashFlow::factory()->create(['station_id' => $overriding->id])->id => $khr->id,
            ],
            'customer_payments' => [
                CustomerPayment::factory()->create(['station_id' => $inheriting->id])->id => $usd->id,
                CustomerPayment::factory()->create(['station_id' => $overriding->id])->id => $khr->id,
            ],
            'fuel_deliveries' => [
                FuelDelivery::factory()->create(['tank_id' => $overridingTank->id])->id => $khr->id,
                FuelDelivery::factory()->create(['tank_id' => Tank::factory()->create(['station_id' => $inheriting->id])->id])->id => $usd->id,
            ],
        ];

        $migration = require database_path('migrations/2026_11_02_041842_add_currency_id_to_transaction_tables.php');
        $migration->down();
        $migration->up();

        foreach ($records as $table => $expectedCurrencies) {
            foreach ($expectedCurrencies as $id => $currencyId) {
                $this->assertDatabaseHas($table, ['id' => $id, 'currency_id' => $currencyId]);
            }
        }
    }
}
