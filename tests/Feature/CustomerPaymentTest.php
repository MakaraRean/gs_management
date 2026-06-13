<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Sale;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_payment_creates_payment_record_and_cash_flow(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $customer = Customer::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('customers.payments.store', $customer), [
                'amount' => 200.00,
                'payment_method' => 'cash',
                'paid_at' => now()->toDateTimeString(),
            ])->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customer_payments', [
            'customer_id' => $customer->id,
            'station_id' => $station->id,
            'amount' => '200.00',
            'payment_method' => 'cash',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('cash_flows', [
            'station_id' => $station->id,
            'direction' => 'in',
            'category' => 'credit_payment',
            'amount' => '200.00',
        ]);
    }

    public function test_recording_a_payment_reduces_outstanding_balance(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $customer = Customer::factory()->create(['business_id' => $business->id]);
        $fuelType = FuelType::factory()->create(['unit_price' => 2.0]);
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id, 'capacity' => 1000, 'current_volume' => 1000]);
        $pump = Pump::factory()->create(['tank_id' => $tank->id]);

        Sale::factory()->create([
            'pump_id' => $pump->id,
            'fuel_type_id' => $fuelType->id,
            'customer_id' => $customer->id,
            'payment_method' => 'credit',
            'total_amount' => 500.00,
        ]);

        $this->assertEquals(500.00, $customer->outstandingBalance());

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('customers.payments.store', $customer), [
                'amount' => 200.00,
                'payment_method' => 'cash',
                'paid_at' => now()->toDateTimeString(),
            ]);

        $this->assertEquals(300.00, $customer->fresh()->outstandingBalance());
    }

    public function test_destroying_a_payment_sets_is_active_false(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $customer = Customer::factory()->create(['business_id' => $business->id]);
        $payment = CustomerPayment::factory()->create([
            'customer_id' => $customer->id,
            'station_id' => $station->id,
        ]);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->delete(route('customers.payments.destroy', [$customer, $payment]))
            ->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customer_payments', ['id' => $payment->id, 'is_active' => false]);
    }
}
