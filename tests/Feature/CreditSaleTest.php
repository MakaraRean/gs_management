<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditSaleTest extends TestCase
{
    use RefreshDatabase;

    private function pumpWithVolume(Station $station, float $price, float $capacity, float $volume): Pump
    {
        $fuelType = FuelType::factory()->create(['unit_price' => $price, 'station_id' => $station->id]);
        $tank = Tank::factory()->create([
            'fuel_type_id' => $fuelType->id,
            'station_id' => $station->id,
            'capacity' => $capacity,
            'current_volume' => $volume,
        ]);

        return Pump::factory()->create(['tank_id' => $tank->id, 'status' => 'active']);
    }

    public function test_a_credit_sale_requires_a_customer_id(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();
        $pump = $this->pumpWithVolume($station, 2.0, 1000, 500);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('sales.store'), [
                'pump_id' => $pump->id,
                'volume' => 50,
                'payment_method' => 'credit',
            ])->assertSessionHasErrors('customer_id');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_a_credit_sale_is_stored_with_customer_and_decrements_tank(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $customer = Customer::factory()->create(['business_id' => $business->id]);
        $pump = $this->pumpWithVolume($station, 2.0, 1000, 500);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('sales.store'), [
                'pump_id' => $pump->id,
                'volume' => 100,
                'payment_method' => 'credit',
                'customer_id' => $customer->id,
            ])->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sales', [
            'pump_id' => $pump->id,
            'customer_id' => $customer->id,
            'payment_method' => 'credit',
            'volume' => '100.00',
            'total_amount' => '200.00',
        ]);

        $this->assertEquals(400.0, (float) $pump->tank->fresh()->current_volume);
    }

    public function test_credit_sale_increases_customer_outstanding_balance(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $customer = Customer::factory()->create(['business_id' => $business->id, 'credit_limit' => 1000]);
        $pump = $this->pumpWithVolume($station, 2.0, 1000, 500);

        $this->assertEquals(0.0, $customer->outstandingBalance());

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('sales.store'), [
                'pump_id' => $pump->id,
                'volume' => 50,
                'payment_method' => 'credit',
                'customer_id' => $customer->id,
            ]);

        $this->assertEquals(100.0, $customer->fresh()->outstandingBalance());
    }
}
