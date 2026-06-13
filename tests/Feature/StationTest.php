<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_stations(): void
    {
        $this->get(route('stations.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_stations_index(): void
    {
        $user = User::factory()->create();
        Station::factory()->count(3)->for(Business::factory()->for($user))->create();

        $this->actingAs($user)
            ->get(route('stations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('stations/index')->has('stations', 3));
    }

    public function test_a_station_can_be_created(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();

        $this->actingAs($user)->post(route('stations.store'), [
            'name' => 'Main Branch',
            'phone' => '012345678',
            'email' => 'main@example.com',
            'address' => '123 Main St',
        ])->assertRedirect(route('stations.index'));

        $this->assertDatabaseHas('stations', [
            'name' => 'Main Branch',
            'business_id' => $business->id,
            'is_active' => true,
        ]);
    }

    public function test_a_station_can_be_updated(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create(['name' => 'Old Name']);

        $this->actingAs($user)->put(route('stations.update', $station), [
            'name' => 'New Name',
        ])->assertRedirect(route('stations.index'));

        $this->assertDatabaseHas('stations', ['id' => $station->id, 'name' => 'New Name']);
    }

    public function test_destroying_a_station_sets_is_active_false(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();

        $this->actingAs($user)
            ->delete(route('stations.destroy', $station))
            ->assertRedirect(route('stations.index'));

        $this->assertDatabaseHas('stations', ['id' => $station->id, 'is_active' => false]);
    }

    public function test_inactive_stations_are_excluded_from_index(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        Station::factory()->for($business)->create(['name' => 'Active']);
        Station::factory()->for($business)->create(['name' => 'Inactive', 'is_active' => false]);

        $this->actingAs($user)
            ->get(route('stations.index'))
            ->assertInertia(fn ($page) => $page->has('stations', 1));
    }
}
