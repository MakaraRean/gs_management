<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_station_index_only_lists_stations_of_own_businesses(): void
    {
        $user = User::factory()->create();
        $own = Station::factory()->for(Business::factory()->for($user))->create();
        Station::factory()->create();

        $this->actingAs($user)
            ->get(route('stations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('stations/index')
                ->has('stations', 1)
                ->where('stations.0.id', $own->id));
    }

    public function test_users_cannot_update_a_station_of_another_users_business(): void
    {
        $user = User::factory()->create();
        Station::factory()->for(Business::factory()->for($user))->create();
        $foreign = Station::factory()->create();

        $this->actingAs($user)
            ->put(route('stations.update', $foreign), ['name' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_users_cannot_switch_to_a_station_of_another_users_business(): void
    {
        $user = User::factory()->create();
        Station::factory()->for(Business::factory()->for($user))->create();
        $foreign = Station::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('station.switch'), ['station_id' => $foreign->id])
            ->assertNotFound();
    }
}
