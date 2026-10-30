<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\FuelDelivery;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Sale;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Timestamps are stored in UTC, but days follow the stations' calendar
 * (Asia/Phnom_Penh, UTC+7): local 00:00–06:59 is still the previous UTC day.
 */
class LocalDayBoundaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cash sales by local time around 30 Oct 2026. Each amount is a different power
     * of two, so a total shows exactly which sales were counted.
     *
     * @var array<string, int>
     */
    private const BOUNDARY_SALES = [
        '2026-10-29 23:59' => 1,
        '2026-10-30 00:00' => 2,
        '2026-10-30 06:59' => 4,
        '2026-10-30 07:00' => 8,
        '2026-10-30 23:59' => 16,
        '2026-10-31 00:00' => 32,
    ];

    private User $user;

    private Station $station;

    private Tank $tank;

    private Pump $pump;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->station = Station::factory()->for(Business::factory()->for($this->user))->create();
        $fuelType = FuelType::factory()->create(['station_id' => $this->station->id]);
        $this->tank = Tank::factory()->create([
            'fuel_type_id' => $fuelType->id,
            'station_id' => $this->station->id,
            'capacity' => 10000,
            'current_volume' => 1000,
        ]);
        $this->pump = Pump::factory()->create(['tank_id' => $this->tank->id]);
    }

    /**
     * A Phnom Penh wall-clock time, as the UTC moment that is stored.
     */
    private function localTime(string $time): Carbon
    {
        return Carbon::parse($time, 'Asia/Phnom_Penh')->utc();
    }

    /**
     * @param  array<string, int>  $amounts  cash sale amounts by local time
     */
    private function sellAt(array $amounts): void
    {
        foreach ($amounts as $time => $amount) {
            Sale::factory()->create([
                'pump_id' => $this->pump->id,
                'fuel_type_id' => $this->tank->fuel_type_id,
                'station_id' => $this->station->id,
                'payment_method' => 'cash',
                'volume' => $amount,
                'total_amount' => $amount,
                'sold_at' => $this->localTime($time),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $query
     */
    private function getPage(string $route, array $query = []): TestResponse
    {
        return $this->actingAs($this->user)
            ->withUnencryptedCookie('station_id', $this->station->id)
            ->get(route($route, $query))
            ->assertOk();
    }

    /**
     * Ledger totals per date for one category.
     *
     * @return array<string, float>
     */
    private function ledgerTotals(TestResponse $response, string $category): array
    {
        return collect($response->inertiaProps('entries'))
            ->where('category', $category)
            ->groupBy('date')
            ->map(fn ($entries) => $entries->sum('amount'))
            ->all();
    }

    public function test_local_time_converts_between_local_days_and_utc(): void
    {
        [$start, $end] = LocalTime::dayRange('2026-10-30', '2026-10-30');

        $this->assertSame('2026-10-29 17:00:00', $start->toDateTimeString());
        $this->assertSame('2026-10-30 16:59:59', $end->toDateTimeString());
        $this->assertSame('2026-10-29 20:00:00', LocalTime::toUtc('2026-10-30T03:00')->toDateTimeString());
        $this->assertNull(LocalTime::toUtc(null));

        $this->assertSame('2026-10-29', LocalTime::dateOf($this->localTime('2026-10-29 23:59')));
        $this->assertSame('2026-10-30', LocalTime::dateOf($this->localTime('2026-10-30 00:00')));
        $this->assertSame('2026-10-30', LocalTime::dateOf($this->localTime('2026-10-30 06:59')));
        $this->assertSame('2026-10-30', LocalTime::dateOf($this->localTime('2026-10-30 07:00')));
    }

    public function test_the_cash_flow_ledger_groups_cash_sales_by_local_day(): void
    {
        $this->sellAt(self::BOUNDARY_SALES);

        $response = $this->getPage('cash-flow.index', ['from' => '2026-10-29', 'to' => '2026-10-31']);

        $this->assertEquals(
            ['2026-10-29' => 1, '2026-10-30' => 30, '2026-10-31' => 32],
            $this->ledgerTotals($response, 'sale'),
        );
    }

    public function test_the_cash_flow_date_filter_covers_the_whole_local_day(): void
    {
        $this->sellAt(self::BOUNDARY_SALES);

        $response = $this->getPage('cash-flow.index', ['from' => '2026-10-30', 'to' => '2026-10-30']);

        $this->assertEquals(['2026-10-30' => 30], $this->ledgerTotals($response, 'sale'));
        $this->assertEquals(30, $response->inertiaProps('summary.total_in'));
    }

    public function test_the_ledger_dates_deliveries_and_manual_entries_by_local_day(): void
    {
        foreach (['2026-10-29 23:59' => 1, '2026-10-30 00:00' => 2] as $time => $cost) {
            FuelDelivery::factory()->create([
                'tank_id' => $this->tank->id,
                'total_cost' => $cost,
                'delivered_at' => $this->localTime($time),
            ]);
        }

        foreach (['2026-10-30 06:59' => 4, '2026-10-30 07:00' => 8] as $time => $amount) {
            CashFlow::factory()->create([
                'station_id' => $this->station->id,
                'direction' => 'out',
                'category' => 'expense',
                'amount' => $amount,
                'occurred_at' => $this->localTime($time),
            ]);
        }

        $response = $this->getPage('cash-flow.index', ['from' => '2026-10-29', 'to' => '2026-10-30']);

        $this->assertEquals(['2026-10-29' => 1, '2026-10-30' => 2], $this->ledgerTotals($response, 'fuel_purchase'));
        $this->assertEquals(['2026-10-30' => 12], $this->ledgerTotals($response, 'expense'));
    }

    public function test_a_credit_payment_at_3am_is_listed_on_that_local_day(): void
    {
        $this->travelTo($this->localTime('2026-10-30 03:00'));
        $customer = Customer::factory()->create(['business_id' => $this->station->business_id]);

        $this->actingAs($this->user)
            ->withUnencryptedCookie('station_id', $this->station->id)
            ->post(route('customers.payments.store', $customer), [
                'amount' => 50,
                'payment_method' => 'cash',
            ])->assertRedirect(route('customers.show', $customer));

        $response = $this->getPage('cash-flow.index');

        $this->assertSame('2026-10-30', $response->inertiaProps('filters.to'));
        $this->assertEquals(['2026-10-30' => 50], $this->ledgerTotals($response, 'credit_payment'));
    }

    public function test_a_manual_cash_entry_date_is_read_as_a_local_day(): void
    {
        $this->actingAs($this->user)
            ->withUnencryptedCookie('station_id', $this->station->id)
            ->post(route('cash-flow.store'), [
                'direction' => 'out',
                'category' => 'expense',
                'amount' => 75,
                'description' => 'Generator fuel',
                'occurred_at' => '2026-10-30',
            ])->assertRedirect(route('cash-flow.index'));

        $this->assertDatabaseHas('cash_flows', [
            'description' => 'Generator fuel',
            'occurred_at' => '2026-10-29 17:00:00',
        ]);

        $response = $this->getPage('cash-flow.index', ['from' => '2026-10-30', 'to' => '2026-10-30']);

        $this->assertEquals(['2026-10-30' => 75], $this->ledgerTotals($response, 'expense'));
    }

    public function test_a_delivery_time_is_read_as_local_time(): void
    {
        $this->actingAs($this->user)
            ->withUnencryptedCookie('station_id', $this->station->id)
            ->post(route('fuel.deliveries.store'), [
                'tank_id' => $this->tank->id,
                'volume' => 100,
                'cost_per_unit' => 1.5,
                'delivered_at' => '2026-10-30T03:00',
            ])->assertRedirect(route('fuel.deliveries.index'));

        $this->assertDatabaseHas('fuel_deliveries', [
            'tank_id' => $this->tank->id,
            'delivered_at' => '2026-10-29 20:00:00',
        ]);

        $response = $this->getPage('cash-flow.index', ['from' => '2026-10-30', 'to' => '2026-10-30']);

        $this->assertEquals(['2026-10-30' => 150], $this->ledgerTotals($response, 'fuel_purchase'));
    }

    public function test_the_dashboard_counts_today_and_the_last_seven_days_in_local_time(): void
    {
        $this->travelTo($this->localTime('2026-10-30 03:00'));
        $this->sellAt(self::BOUNDARY_SALES);
        $this->sellAt(['2026-10-23 23:59' => 64, '2026-10-24 00:00' => 128]);

        $response = $this->getPage('dashboard');

        $this->assertEquals(30, $response->inertiaProps('stats.today_revenue'));
        $this->assertSame(4, $response->inertiaProps('stats.today_transactions'));
        $this->assertEquals(
            ['2026-10-24' => 128, '2026-10-29' => 1, '2026-10-30' => 30],
            collect($response->inertiaProps('revenueByDay'))->pluck('revenue', 'day')->all(),
        );
    }

    public function test_sales_analysis_groups_and_filters_by_local_day(): void
    {
        $this->sellAt(self::BOUNDARY_SALES);

        $threeDays = $this->getPage('analytics.index', ['from' => '2026-10-29', 'to' => '2026-10-31']);
        $oneDay = $this->getPage('analytics.index', ['from' => '2026-10-30', 'to' => '2026-10-30']);

        $this->assertEquals(
            ['2026-10-29' => 1, '2026-10-30' => 30, '2026-10-31' => 32],
            collect($threeDays->inertiaProps('revenueByDay'))->pluck('revenue', 'day')->all(),
        );
        $this->assertEquals(30, $oneDay->inertiaProps('totals.revenue'));
        $this->assertSame(4, $oneDay->inertiaProps('totals.transactions'));
    }

    public function test_reports_group_and_filter_by_local_day(): void
    {
        $this->sellAt(self::BOUNDARY_SALES);

        $dailySales = $this->getPage('reports.index', ['type' => 'daily_sales', 'from' => '2026-10-29', 'to' => '2026-10-31']);
        $cashFlow = $this->getPage('reports.index', ['type' => 'cash_flow', 'from' => '2026-10-30', 'to' => '2026-10-30']);

        $this->assertEquals(
            ['2026-10-29' => 1, '2026-10-30' => 30, '2026-10-31' => 32],
            collect($dailySales->inertiaProps('report.rows'))->pluck('revenue', 'date')->all(),
        );
        $this->assertEquals(30, $cashFlow->inertiaProps('report.rows.0.amount'));
    }

    public function test_the_sales_list_date_filter_covers_the_whole_local_day(): void
    {
        $this->sellAt(self::BOUNDARY_SALES);

        $response = $this->getPage('sales.index', ['from' => '2026-10-30', 'to' => '2026-10-30']);

        $sales = collect($response->inertiaProps('sales.data'));

        $this->assertCount(4, $sales);
        $this->assertEquals(30, $sales->sum('total_amount'));
    }

    public function test_the_migration_converts_delivery_times_stored_before_the_fix_to_utc(): void
    {
        $typedBeforeTheFix = FuelDelivery::factory()->create([
            'tank_id' => $this->tank->id,
            'delivered_at' => '2026-10-30 14:00:00',
            'created_at' => '2026-10-30 07:46:32',
        ]);
        $withoutCreatedAt = FuelDelivery::factory()->create([
            'tank_id' => $this->tank->id,
            'delivered_at' => '2026-10-30 20:00:00',
            'created_at' => null,
        ]);
        $storedInUtc = FuelDelivery::factory()->create([
            'tank_id' => $this->tank->id,
            'delivered_at' => '2026-10-30 07:50:00',
            'created_at' => '2026-10-30 07:46:33',
        ]);
        $migration = require database_path('migrations/2026_10_30_050735_convert_fuel_deliveries_delivered_at_to_utc.php');

        $migration->up();

        $this->assertDatabaseHas('fuel_deliveries', ['id' => $typedBeforeTheFix->id, 'delivered_at' => '2026-10-30 07:00:00']);
        $this->assertDatabaseHas('fuel_deliveries', ['id' => $withoutCreatedAt->id, 'delivered_at' => '2026-10-30 13:00:00']);
        $this->assertDatabaseHas('fuel_deliveries', ['id' => $storedInUtc->id, 'delivered_at' => '2026-10-30 07:50:00']);

        $migration->down();

        $this->assertDatabaseHas('fuel_deliveries', ['id' => $typedBeforeTheFix->id, 'delivered_at' => '2026-10-30 14:00:00']);
        $this->assertDatabaseHas('fuel_deliveries', ['id' => $withoutCreatedAt->id, 'delivered_at' => '2026-10-30 20:00:00']);
        $this->assertDatabaseHas('fuel_deliveries', ['id' => $storedInUtc->id, 'delivered_at' => '2026-10-30 07:50:00']);
    }
}
