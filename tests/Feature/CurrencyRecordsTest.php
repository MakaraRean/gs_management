<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CashFlow;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\FuelDelivery;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyRecordsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Station $usdStation;

    private Station $khrStation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->for($this->user)->create();
        $this->usdStation = Station::factory()->for($this->business)->create();
        $this->khrStation = Station::factory()->for($this->business)->create([
            'currency_id' => CurrencyFactory::seeded('KHR')->id,
        ]);
    }

    private function actingAtStation(Station $station): static
    {
        return $this->actingAs($this->user)->withUnencryptedCookie('station_id', $station->id);
    }

    private function tankAt(Station $station, float $price = 1.25): Tank
    {
        $fuelType = FuelType::factory()->create(['unit_price' => $price, 'station_id' => $station->id]);

        return Tank::factory()->create([
            'fuel_type_id' => $fuelType->id,
            'station_id' => $station->id,
            'capacity' => 10000,
            'current_volume' => 5000,
        ]);
    }

    public function test_a_sale_is_recorded_in_the_stations_inherited_currency(): void
    {
        $pump = Pump::factory()->create(['tank_id' => $this->tankAt($this->usdStation, 2.0)->id, 'status' => 'active']);

        $this->actingAtStation($this->usdStation)
            ->post(route('sales.store'), ['pump_id' => $pump->id, 'volume' => 10, 'payment_method' => 'cash'])
            ->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sales', [
            'station_id' => $this->usdStation->id,
            'currency_id' => CurrencyFactory::seeded('USD')->id,
            'total_amount' => '20.00',
        ]);
    }

    public function test_a_sale_is_recorded_and_rounded_in_the_stations_own_currency(): void
    {
        $pump = Pump::factory()->create(['tank_id' => $this->tankAt($this->khrStation, 1.25)->id, 'status' => 'active']);

        $this->actingAtStation($this->khrStation)
            ->post(route('sales.store'), ['pump_id' => $pump->id, 'volume' => 3.3, 'payment_method' => 'cash'])
            ->assertRedirect(route('sales.index'));

        // 3.3 x 1.25 = 4.125, whole riel for KHR.
        $this->assertDatabaseHas('sales', [
            'station_id' => $this->khrStation->id,
            'currency_id' => CurrencyFactory::seeded('KHR')->id,
            'total_amount' => '4.00',
        ]);
    }

    public function test_a_cash_flow_entry_is_recorded_in_the_stations_currency(): void
    {
        $this->actingAtStation($this->khrStation)
            ->post(route('cash-flow.store'), [
                'direction' => 'out',
                'category' => 'expense',
                'amount' => 25000,
                'description' => 'Cleaning',
                'occurred_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('cash-flow.index'));

        $this->assertDatabaseHas('cash_flows', [
            'station_id' => $this->khrStation->id,
            'currency_id' => CurrencyFactory::seeded('KHR')->id,
            'amount' => '25000.00',
        ]);
    }

    public function test_a_customer_payment_and_its_cash_flow_row_share_the_stations_currency(): void
    {
        $customer = Customer::factory()->create(['business_id' => $this->business->id]);
        $khr = CurrencyFactory::seeded('KHR');

        $this->actingAtStation($this->khrStation)
            ->post(route('customers.payments.store', $customer), [
                'amount' => 40000,
                'payment_method' => 'cash',
                'paid_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customer_payments', ['customer_id' => $customer->id, 'currency_id' => $khr->id]);
        $this->assertDatabaseHas('cash_flows', ['category' => 'credit_payment', 'currency_id' => $khr->id]);
    }

    public function test_a_delivery_is_recorded_and_rounded_in_the_tank_stations_currency(): void
    {
        $tank = $this->tankAt($this->khrStation);

        $this->actingAtStation($this->khrStation)
            ->post(route('fuel.deliveries.store'), [
                'tank_id' => $tank->id,
                'volume' => 3,
                'cost_per_unit' => 1234.6,
                'delivered_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('fuel.deliveries.index'));

        // The unit cost is whole riel, and the total follows it.
        $this->assertDatabaseHas('fuel_deliveries', [
            'tank_id' => $tank->id,
            'currency_id' => CurrencyFactory::seeded('KHR')->id,
            'cost_per_unit' => '1235.00',
            'total_cost' => '3705.00',
        ]);
    }

    public function test_manual_amounts_and_fuel_prices_are_rounded_to_the_stations_currency(): void
    {
        $this->actingAtStation($this->khrStation)
            ->post(route('cash-flow.store'), [
                'direction' => 'in',
                'category' => 'deposit',
                'amount' => 1000.4,
                'description' => 'Float',
                'occurred_at' => now()->toDateTimeString(),
            ]);
        $this->actingAtStation($this->khrStation)
            ->post(route('fuel.types.store'), ['name' => 'Diesel', 'unit_price' => 4500.6, 'unit' => 'L', 'color' => '#f59e0b']);
        $this->actingAtStation($this->usdStation)
            ->post(route('fuel.types.store'), ['name' => 'Diesel', 'unit_price' => 1.456, 'unit' => 'L', 'color' => '#f59e0b']);

        $this->assertDatabaseHas('cash_flows', ['description' => 'Float', 'amount' => '1000.00']);
        $this->assertDatabaseHas('fuel_types', ['station_id' => $this->khrStation->id, 'unit_price' => '4501.00']);
        $this->assertDatabaseHas('fuel_types', ['station_id' => $this->usdStation->id, 'unit_price' => '1.46']);
    }

    public function test_a_payment_can_be_recorded_in_the_currency_the_customer_owes(): void
    {
        $customer = Customer::factory()->create(['business_id' => $this->business->id]);
        $khr = CurrencyFactory::seeded('KHR');

        $this->actingAtStation($this->usdStation)
            ->post(route('customers.payments.store', $customer), [
                'amount' => 4000.6,
                'currency_id' => $khr->id,
                'payment_method' => 'cash',
                'paid_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customer_payments', ['customer_id' => $customer->id, 'currency_id' => $khr->id, 'amount' => '4001.00']);
        $this->assertDatabaseHas('cash_flows', ['category' => 'credit_payment', 'currency_id' => $khr->id, 'amount' => '4001.00']);
    }

    public function test_a_payment_currency_must_be_an_active_currency(): void
    {
        $customer = Customer::factory()->create(['business_id' => $this->business->id]);
        $retired = Currency::factory()->inactive()->create();

        foreach ([999999, $retired->id] as $currencyId) {
            $this->actingAtStation($this->usdStation)
                ->from(route('customers.show', $customer))
                ->post(route('customers.payments.store', $customer), [
                    'amount' => 10,
                    'currency_id' => $currencyId,
                    'payment_method' => 'cash',
                ])
                ->assertSessionHasErrors('currency_id');
        }

        $this->assertDatabaseCount('customer_payments', 0);
    }

    public function test_a_new_record_without_a_currency_is_stamped_from_its_station(): void
    {
        $khr = CurrencyFactory::seeded('KHR');
        $usd = CurrencyFactory::seeded('USD');

        $cashFlow = CashFlow::create(['station_id' => $this->khrStation->id, 'direction' => 'in', 'category' => 'deposit', 'amount' => 5, 'description' => 'x', 'occurred_at' => now()]);
        $delivery = FuelDelivery::create(['tank_id' => $this->tankAt($this->khrStation)->id, 'volume' => 1, 'cost_per_unit' => 1, 'total_cost' => 1, 'delivered_at' => now()]);
        $explicit = CashFlow::create(['station_id' => $this->khrStation->id, 'currency_id' => $usd->id, 'direction' => 'in', 'category' => 'deposit', 'amount' => 5, 'description' => 'y', 'occurred_at' => now()]);

        $this->assertSame($khr->id, $cashFlow->currency_id);
        $this->assertSame($khr->id, $delivery->currency_id);
        $this->assertSame($usd->id, $explicit->currency_id);
    }

    public function test_a_record_with_no_station_to_take_a_currency_from_cannot_be_created(): void
    {
        $this->expectException(QueryException::class);

        CashFlow::create(['direction' => 'in', 'category' => 'deposit', 'amount' => 5, 'description' => 'x', 'occurred_at' => now()]);
    }

    public function test_the_station_scopes_follow_the_station_and_include_deliveries_of_deactivated_tanks(): void
    {
        $khr = CurrencyFactory::seeded('KHR');
        $tank = $this->tankAt($this->usdStation);
        $delivery = FuelDelivery::factory()->create(['tank_id' => $tank->id]);
        FuelDelivery::factory()->create(['tank_id' => $this->tankAt($this->khrStation)->id]);
        $tank->deactivate();
        $mine = CashFlow::factory()->create(['station_id' => $this->usdStation->id]);
        CashFlow::factory()->create(['station_id' => $this->usdStation->id, 'currency_id' => $khr->id]);
        CashFlow::factory()->create(['station_id' => $this->khrStation->id]);

        $this->assertSame([$delivery->id], FuelDelivery::query()->forStation($this->usdStation)->pluck('id')->all());
        $this->assertSame([$mine->id], CashFlow::query()->inStationCurrency($this->usdStation, $this->usdStation->effective_currency)->pluck('id')->all());
    }

    public function test_a_delivery_into_another_stations_tank_is_forbidden(): void
    {
        $otherTank = $this->tankAt($this->khrStation);

        $this->actingAtStation($this->usdStation)
            ->post(route('fuel.deliveries.store'), [
                'tank_id' => $otherTank->id,
                'volume' => 1000,
                'cost_per_unit' => 1.5,
                'delivered_at' => now()->toDateTimeString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('fuel_deliveries', 0);
        $this->assertEquals(5000.0, (float) $otherTank->fresh()->current_volume);
    }

    public function test_the_deliveries_page_lists_only_the_active_stations_deliveries(): void
    {
        $mine = FuelDelivery::factory()->create(['tank_id' => $this->tankAt($this->usdStation)->id]);
        FuelDelivery::factory()->create(['tank_id' => $this->tankAt($this->khrStation)->id]);

        $this->actingAtStation($this->usdStation)
            ->get(route('fuel.deliveries.index'))
            ->assertInertia(fn ($page) => $page
                ->has('deliveries', 1)
                ->where('deliveries.0.id', $mine->id)
                ->where('deliveries.0.currency_id', $mine->currency_id)
                ->has('tanks', 1));
    }

    public function test_changing_a_stations_currency_leaves_its_old_records_in_the_old_currency(): void
    {
        $usd = CurrencyFactory::seeded('USD');
        $entry = CashFlow::factory()->create(['station_id' => $this->usdStation->id]);

        $this->usdStation->update(['currency_id' => CurrencyFactory::seeded('KHR')->id]);

        $this->actingAtStation($this->usdStation)
            ->post(route('cash-flow.store'), [
                'direction' => 'in',
                'category' => 'deposit',
                'amount' => 4000,
                'description' => 'After the switch',
                'occurred_at' => now()->toDateTimeString(),
            ]);

        $this->assertSame($usd->id, $entry->fresh()->currency_id);
        $this->assertDatabaseHas('cash_flows', [
            'description' => 'After the switch',
            'currency_id' => CurrencyFactory::seeded('KHR')->id,
        ]);
    }
}
