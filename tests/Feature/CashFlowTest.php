<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CashFlow;
use App\Models\FuelDelivery;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Sale;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CashFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_cash_flow(): void
    {
        $this->get(route('cash-flow.index'))->assertRedirect(route('login'));
    }

    public function test_net_cash_aggregates_sales_deliveries_and_manual_entries(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();

        $fuelType = FuelType::factory()->create(['station_id' => $station->id]);
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id, 'station_id' => $station->id]);
        $pump = Pump::factory()->create(['tank_id' => $tank->id]);

        Sale::factory()->create([
            'pump_id' => $pump->id,
            'fuel_type_id' => $fuelType->id,
            'station_id' => $station->id,
            'payment_method' => 'cash',
            'total_amount' => 100,
            'sold_at' => now(),
        ]);
        // Card sales must NOT count toward cash.
        Sale::factory()->create([
            'pump_id' => $pump->id,
            'fuel_type_id' => $fuelType->id,
            'station_id' => $station->id,
            'payment_method' => 'card',
            'total_amount' => 999,
            'sold_at' => now(),
        ]);

        FuelDelivery::factory()->create([
            'tank_id' => $tank->id,
            'total_cost' => 40,
            'delivered_at' => now(),
        ]);

        CashFlow::factory()->create([
            'station_id' => $station->id,
            'direction' => 'in',
            'category' => 'other_income',
            'amount' => 20,
            'occurred_at' => now(),
        ]);
        CashFlow::factory()->create([
            'station_id' => $station->id,
            'direction' => 'out',
            'category' => 'expense',
            'amount' => 10,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('cash-flow.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('cash-flow/index')
            ->where('summary.total_in', fn ($value) => abs((float) $value - 120) < 0.01)
            ->where('summary.total_out', fn ($value) => abs((float) $value - 50) < 0.01)
            ->where('summary.net', fn ($value) => abs((float) $value - 70) < 0.01)
        );
    }

    public function test_a_manual_cash_entry_can_be_recorded(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('cash-flow.store'), [
                'direction' => 'out',
                'category' => 'expense',
                'amount' => 75.5,
                'description' => 'Pump maintenance',
                'occurred_at' => now()->toDateString(),
            ])->assertRedirect(route('cash-flow.index'));

        $this->assertDatabaseHas('cash_flows', [
            'station_id' => $station->id,
            'direction' => 'out',
            'category' => 'expense',
            'amount' => '75.50',
            'description' => 'Pump maintenance',
            'user_id' => $user->id,
        ]);
    }
}
