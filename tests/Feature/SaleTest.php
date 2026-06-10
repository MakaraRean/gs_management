<?php

namespace Tests\Feature;

use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Sale;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private function pumpWithVolume(float $price, float $capacity, float $volume): Pump
    {
        $fuelType = FuelType::factory()->create(['unit_price' => $price]);
        $tank = Tank::factory()->create([
            'fuel_type_id' => $fuelType->id,
            'capacity' => $capacity,
            'current_volume' => $volume,
        ]);

        return Pump::factory()->create(['tank_id' => $tank->id, 'status' => 'active']);
    }

    public function test_guests_are_redirected_from_sales(): void
    {
        $this->get(route('sales.index'))->assertRedirect(route('login'));
    }

    public function test_recording_a_sale_decrements_the_tank_and_persists_the_sale(): void
    {
        $user = User::factory()->create();
        $pump = $this->pumpWithVolume(price: 2.0, capacity: 1000, volume: 500);

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'pump_id' => $pump->id,
            'volume' => 100,
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sales', [
            'pump_id' => $pump->id,
            'fuel_type_id' => $pump->tank->fuel_type_id,
            'volume' => '100.00',
            'unit_price' => '2.00',
            'total_amount' => '200.00',
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(400.0, (float) $pump->tank->fresh()->current_volume);
    }

    public function test_a_sale_cannot_exceed_available_tank_volume(): void
    {
        $user = User::factory()->create();
        $pump = $this->pumpWithVolume(price: 2.0, capacity: 1000, volume: 50);

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'pump_id' => $pump->id,
            'volume' => 100,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors('volume');

        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(50.0, (float) $pump->tank->fresh()->current_volume);
    }

    public function test_deleting_a_sale_removes_the_record(): void
    {
        $user = User::factory()->create();
        $pump = $this->pumpWithVolume(price: 2.0, capacity: 1000, volume: 500);
        $sale = Sale::factory()->create([
            'pump_id' => $pump->id,
            'fuel_type_id' => $pump->tank->fuel_type_id,
        ]);

        $this->actingAs($user)
            ->delete(route('sales.destroy', $sale))
            ->assertRedirect(route('sales.index'));

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }
}
