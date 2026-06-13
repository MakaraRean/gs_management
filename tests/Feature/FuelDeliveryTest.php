<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\FuelType;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_delivery_increases_tank_volume_and_computes_total(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();
        $fuelType = FuelType::factory()->create(['station_id' => $station->id]);
        $tank = Tank::factory()->create([
            'fuel_type_id' => $fuelType->id,
            'station_id' => $station->id,
            'capacity' => 10000,
            'current_volume' => 2000,
        ]);

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('fuel.deliveries.store'), [
                'tank_id' => $tank->id,
                'volume' => 3000,
                'cost_per_unit' => 1.5,
                'delivered_at' => now()->toDateTimeString(),
            ]);

        $response->assertRedirect(route('fuel.deliveries.index'));

        $this->assertDatabaseHas('fuel_deliveries', [
            'tank_id' => $tank->id,
            'volume' => '3000.00',
            'cost_per_unit' => '1.50',
            'total_cost' => '4500.00',
        ]);

        $this->assertEquals(5000.0, (float) $tank->fresh()->current_volume);
    }

    public function test_delivery_volume_is_capped_at_tank_capacity(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();
        $fuelType = FuelType::factory()->create(['station_id' => $station->id]);
        $tank = Tank::factory()->create([
            'fuel_type_id' => $fuelType->id,
            'station_id' => $station->id,
            'capacity' => 10000,
            'current_volume' => 8000,
        ]);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('fuel.deliveries.store'), [
                'tank_id' => $tank->id,
                'volume' => 5000,
                'cost_per_unit' => 1.5,
                'delivered_at' => now()->toDateTimeString(),
            ]);

        $this->assertEquals(10000.0, (float) $tank->fresh()->current_volume);
    }
}
