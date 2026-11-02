<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Business;
use App\Models\CashFlow;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FuelDelivery;
use App\Models\Sale;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard, reports, analytics, the cash ledger and customer balances total the active
 * station's records in its current currency, so a total never mixes stations or currencies.
 */
class CurrencyReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Station $usdStation;

    private Station $khrStation;

    private Currency $khr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->khr = CurrencyFactory::seeded('KHR');
        $this->user = User::factory()->create();
        $this->business = Business::factory()->for($this->user)->create();
        $this->usdStation = Station::factory()->for($this->business)->create();
        $this->khrStation = Station::factory()->for($this->business)->create(['currency_id' => $this->khr->id]);
    }

    private function actingAtStation(Station $station): static
    {
        return $this->actingAs($this->user)->withUnencryptedCookie('station_id', $station->id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function sale(Station $station, float $amount, array $attributes = []): Sale
    {
        return Sale::factory()->create([
            'station_id' => $station->id,
            'payment_method' => 'cash',
            'volume' => 10,
            'total_amount' => $amount,
            'sold_at' => now(),
            ...$attributes,
        ]);
    }

    /**
     * One counted sale at the USD station plus two that must be left out: the same station
     * recorded in a previous currency, and another station altogether.
     */
    private function seedSalesToIgnore(): void
    {
        $this->sale($this->usdStation, 100);
        $this->sale($this->usdStation, 5000, ['currency_id' => $this->khr->id]);
        $this->sale($this->khrStation, 400000);
    }

    private static function amount(float $expected): \Closure
    {
        return fn ($actual): bool => (float) $actual === $expected;
    }

    public function test_the_dashboard_totals_only_the_active_stations_sales_in_its_current_currency(): void
    {
        $this->seedSalesToIgnore();

        $this->actingAtStation($this->usdStation)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.today_revenue', self::amount(100.0))
                ->where('stats.today_transactions', 1)
                ->has('recentSales', 1)
                ->has('revenueByDay', 1));
    }

    public function test_dashboard_totals_are_rounded_to_the_stations_currency_digits(): void
    {
        $this->sale($this->khrStation, 1000.40);
        $this->sale($this->khrStation, 2000.40);

        $this->actingAtStation($this->khrStation)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('stats.today_revenue', self::amount(3001.0)));
    }

    public function test_the_dashboard_net_cash_and_tanks_follow_the_active_station(): void
    {
        $this->sale($this->usdStation, 100);
        $this->sale($this->usdStation, 900, ['currency_id' => $this->khr->id]);
        CashFlow::factory()->create(['station_id' => $this->usdStation->id, 'direction' => 'in', 'amount' => 20]);
        CashFlow::factory()->create(['station_id' => $this->khrStation->id, 'direction' => 'in', 'amount' => 777]);
        FuelDelivery::factory()->create([
            'tank_id' => Tank::factory()->create(['station_id' => $this->usdStation->id])->id,
            'total_cost' => 30,
        ]);
        Tank::factory()->create(['station_id' => $this->khrStation->id]);

        $this->actingAtStation($this->usdStation)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.net_cash', self::amount(90.0))
                ->has('tanks', 1));
    }

    public function test_the_dashboard_of_a_business_without_stations_has_empty_totals(): void
    {
        $owner = User::factory()->create();
        Business::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.today_revenue', 0)
                ->where('stats.today_transactions', 0)
                ->where('tanks', [])
                ->where('recentSales', []));
    }

    public function test_the_daily_sales_report_covers_only_the_active_station_and_currency(): void
    {
        $this->seedSalesToIgnore();

        $this->actingAtStation($this->usdStation)
            ->get(route('reports.index', ['type' => 'daily_sales']))
            ->assertInertia(fn ($page) => $page
                ->component('reports/index')
                ->has('report.rows', 1)
                ->where('report.totals.revenue', self::amount(100.0))
                ->where('report.totals.transactions', 1)
                ->where('report.money_keys', ['revenue']));
    }

    public function test_the_cash_flow_report_covers_only_the_active_station_and_currency(): void
    {
        $this->seedSalesToIgnore();
        $this->sale($this->usdStation, 40, ['payment_method' => 'card']);
        CashFlow::factory()->create(['station_id' => $this->usdStation->id, 'direction' => 'out', 'amount' => 10, 'occurred_at' => now()]);
        CashFlow::factory()->create(['station_id' => $this->khrStation->id, 'direction' => 'out', 'amount' => 999, 'occurred_at' => now()]);

        $this->actingAtStation($this->usdStation)
            ->get(route('reports.index', ['type' => 'cash_flow']))
            ->assertInertia(fn ($page) => $page
                ->where('report.totals.net_cash', self::amount(90.0))
                ->where('report.totals.card_sales', self::amount(40.0))
                ->where('report.money_keys', ['amount', 'net_cash', 'card_sales']));
    }

    public function test_the_inventory_report_lists_only_the_active_stations_tanks(): void
    {
        Tank::factory()->create(['station_id' => $this->usdStation->id]);
        Tank::factory()->count(2)->create(['station_id' => $this->khrStation->id]);

        $this->actingAtStation($this->usdStation)
            ->get(route('reports.index', ['type' => 'inventory']))
            ->assertInertia(fn ($page) => $page->has('report.rows', 1)->where('report.money_keys', []));
    }

    public function test_sales_analysis_covers_only_the_active_station_and_currency(): void
    {
        $this->seedSalesToIgnore();

        $this->actingAtStation($this->usdStation)
            ->get(route('analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->where('totals.revenue', self::amount(100.0))
                ->where('totals.transactions', 1)
                ->where('totals.average_ticket', self::amount(100.0))
                ->has('byPump', 1)
                ->has('byFuelType', 1));
    }

    public function test_sales_analysis_rounds_to_the_stations_currency_digits(): void
    {
        $this->sale($this->khrStation, 1000.40);
        $this->sale($this->khrStation, 2000.40);

        $this->actingAtStation($this->khrStation)
            ->get(route('analytics.index'))
            ->assertInertia(fn ($page) => $page
                ->where('totals.revenue', self::amount(3001.0))
                ->where('totals.average_ticket', self::amount(1501.0)));
    }

    public function test_the_cash_ledger_covers_only_the_active_station_and_currency(): void
    {
        $this->seedSalesToIgnore();
        CashFlow::factory()->create(['station_id' => $this->usdStation->id, 'direction' => 'in', 'amount' => 20, 'occurred_at' => now()]);
        CashFlow::factory()->create(['station_id' => $this->usdStation->id, 'direction' => 'out', 'amount' => 5, 'currency_id' => $this->khr->id, 'occurred_at' => now()]);

        $this->actingAtStation($this->usdStation)
            ->get(route('cash-flow.index'))
            ->assertInertia(fn ($page) => $page
                ->has('entries', 2)
                ->where('summary.total_in', self::amount(120.0))
                ->where('summary.total_out', self::amount(0.0)));
    }

    public function test_a_customers_balance_counts_only_credit_and_payments_in_the_current_currency(): void
    {
        $customer = Customer::factory()->create(['business_id' => $this->business->id, 'station_id' => null]);

        $this->sale($this->usdStation, 300, ['customer_id' => $customer->id, 'payment_method' => 'credit']);
        $this->sale($this->khrStation, 5000, ['customer_id' => $customer->id, 'payment_method' => 'credit']);
        CustomerPayment::factory()->create(['customer_id' => $customer->id, 'station_id' => $this->usdStation->id, 'amount' => 100]);

        $this->assertEquals(200.0, $customer->outstandingBalance(CurrencyFactory::seeded('USD')));
        $this->assertEquals(5000.0, $customer->outstandingBalance($this->khr));
        $this->assertEquals(5200.0, $customer->outstandingBalance());

        $this->actingAtStation($this->usdStation)
            ->get(route('customers.index'))
            ->assertInertia(fn ($page) => $page->where('customers.0.outstanding_balance', self::amount(200.0)));

        $this->actingAtStation($this->khrStation)
            ->get(route('customers.index'))
            ->assertInertia(fn ($page) => $page->where('customers.0.outstanding_balance', self::amount(5000.0)));

        $this->actingAtStation($this->khrStation)
            ->get(route('customers.show', $customer))
            ->assertInertia(fn ($page) => $page
                ->where('customer.outstanding_balance', self::amount(5000.0))
                ->where('creditSales.data.0.currency_id', fn ($id) => in_array($id, [CurrencyFactory::seeded('USD')->id, $this->khr->id], true)));
    }

    public function test_pending_deletion_requests_carry_the_currency_of_their_record(): void
    {
        $cashier = User::factory()->cashier()->create(['business_id' => $this->business->id]);
        $sale = $this->sale($this->khrStation, 4500);
        $sale->requestDeletion($cashier, 'Duplicate entry', $this->business, $this->khrStation);

        $this->actingAtStation($this->usdStation)
            ->get(route('deletion-requests.index'))
            ->assertInertia(fn ($page) => $page
                ->has('requests', 1)
                ->where('requests.0.currency_id', $this->khr->id)
                ->where('requests.0.amount', self::amount(4500.0)));
    }

    public function test_a_non_owner_admin_gets_reports_scoped_to_the_station(): void
    {
        $this->seedSalesToIgnore();
        $admin = User::factory()->create(['business_id' => $this->business->id, 'role' => Role::Admin]);

        $this->actingAs($admin)
            ->withUnencryptedCookie('station_id', $this->usdStation->id)
            ->get(route('reports.index', ['type' => 'daily_sales']))
            ->assertInertia(fn ($page) => $page->where('report.totals.revenue', self::amount(100.0)));
    }

    public function test_a_cashier_gets_sales_analysis_scoped_to_the_station(): void
    {
        $this->seedSalesToIgnore();
        $cashier = User::factory()->cashier()->create(['business_id' => $this->business->id]);

        $this->actingAs($cashier)
            ->withUnencryptedCookie('station_id', $this->usdStation->id)
            ->get(route('analytics.index'))
            ->assertInertia(fn ($page) => $page->where('totals.revenue', self::amount(100.0)));
    }

    public function test_deliveries_into_a_deactivated_tank_still_count(): void
    {
        $tank = Tank::factory()->create(['station_id' => $this->usdStation->id]);
        FuelDelivery::factory()->create(['tank_id' => $tank->id, 'total_cost' => 30, 'delivered_at' => now()]);
        $tank->deactivate();

        $this->actingAtStation($this->usdStation)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('stats.net_cash', self::amount(-30.0)));
        $this->actingAtStation($this->usdStation)
            ->get(route('reports.index', ['type' => 'cash_flow']))
            ->assertInertia(fn ($page) => $page->where('report.totals.net_cash', self::amount(-30.0)));
        $this->actingAtStation($this->usdStation)
            ->get(route('cash-flow.index'))
            ->assertInertia(fn ($page) => $page->has('entries', 1)->where('summary.total_out', self::amount(30.0)));
        $this->actingAtStation($this->usdStation)
            ->get(route('fuel.deliveries.index'))
            ->assertInertia(fn ($page) => $page->has('deliveries', 1));
    }

    public function test_a_customer_sees_what_they_owe_in_other_currencies(): void
    {
        $customer = Customer::factory()->create(['business_id' => $this->business->id, 'station_id' => null]);
        $usd = CurrencyFactory::seeded('USD');

        $this->sale($this->usdStation, 300, ['customer_id' => $customer->id, 'payment_method' => 'credit']);
        $this->sale($this->khrStation, 5000, ['customer_id' => $customer->id, 'payment_method' => 'credit']);
        CustomerPayment::factory()->create(['customer_id' => $customer->id, 'station_id' => $this->usdStation->id, 'amount' => 100]);

        $this->assertEquals([$usd->id => 200.0, $this->khr->id => 5000.0], $customer->outstandingBalances());

        $this->actingAtStation($this->usdStation)
            ->get(route('customers.index'))
            ->assertInertia(fn ($page) => $page
                ->where('customers.0.outstanding_balance', self::amount(200.0))
                ->has('customers.0.other_balances', 1)
                ->where('customers.0.other_balances.0.currency_id', $this->khr->id)
                ->where('customers.0.other_balances.0.amount', self::amount(5000.0)));

        $this->actingAtStation($this->khrStation)
            ->get(route('customers.show', $customer))
            ->assertInertia(fn ($page) => $page
                ->where('customer.outstanding_balance', self::amount(5000.0))
                ->where('customer.other_balances.0.currency_id', $usd->id)
                ->where('customer.other_balances.0.amount', self::amount(200.0)));
    }

    public function test_a_balance_paid_off_in_its_currency_is_no_longer_listed(): void
    {
        $customer = Customer::factory()->create(['business_id' => $this->business->id, 'station_id' => null]);

        $this->sale($this->khrStation, 5000, ['customer_id' => $customer->id, 'payment_method' => 'credit']);
        CustomerPayment::factory()->create(['customer_id' => $customer->id, 'station_id' => $this->khrStation->id, 'amount' => 5000]);

        $this->assertSame([], $customer->outstandingBalances());
    }
}
