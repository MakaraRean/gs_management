<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Currency;
use App\Models\Station;
use App\Models\User;
use Database\Factories\CurrencyFactory;
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

    public function test_a_station_created_without_a_currency_inherits_the_business_currency(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();

        $this->actingAs($user)->post(route('stations.store'), ['name' => 'Main Branch'])
            ->assertRedirect(route('stations.index'));

        $station = Station::query()->where('name', 'Main Branch')->firstOrFail();

        $this->assertNull($station->currency_id);
        $this->assertSame($business->currency_id, $station->effective_currency->id);
    }

    public function test_a_station_can_use_a_different_currency_from_its_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->for($user)->create();
        $khr = CurrencyFactory::seeded('KHR');

        $this->actingAs($user)->post(route('stations.store'), ['name' => 'Border Branch', 'currency_id' => $khr->id])
            ->assertRedirect(route('stations.index'));

        $station = Station::query()->where('name', 'Border Branch')->firstOrFail();

        $this->assertSame($khr->id, $station->currency_id);
        $this->assertSame('KHR', $station->effective_currency->code);
    }

    public function test_a_station_can_switch_currency_and_go_back_to_inheriting(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->for($business)->create(['name' => 'Branch']);
        $khr = CurrencyFactory::seeded('KHR');

        $this->actingAs($user)->put(route('stations.update', $station), ['name' => 'Branch', 'currency_id' => $khr->id]);
        $this->assertDatabaseHas('stations', ['id' => $station->id, 'currency_id' => $khr->id]);

        $this->actingAs($user)->put(route('stations.update', $station), ['name' => 'Branch', 'currency_id' => null]);
        $this->assertDatabaseHas('stations', ['id' => $station->id, 'currency_id' => null]);
        $this->assertSame($business->currency_id, $station->fresh()->effective_currency->id);
    }

    public function test_a_station_currency_must_be_an_active_currency(): void
    {
        $user = User::factory()->create();
        Business::factory()->for($user)->create();
        $retired = Currency::factory()->inactive()->create();

        foreach ([999999, $retired->id] as $currencyId) {
            $this->actingAs($user)
                ->from(route('stations.index'))
                ->post(route('stations.store'), ['name' => 'Bad Branch', 'currency_id' => $currencyId])
                ->assertSessionHasErrors('currency_id');
        }

        $this->assertDatabaseMissing('stations', ['name' => 'Bad Branch']);
    }

    public function test_an_unrelated_edit_still_saves_after_the_stations_currency_was_retired(): void
    {
        $user = User::factory()->create();
        $khr = CurrencyFactory::seeded('KHR');
        $station = Station::factory()->for(Business::factory()->for($user))->create(['name' => 'Old', 'currency_id' => $khr->id]);
        $khr->update(['is_active' => false]);

        $this->actingAs($user)
            ->put(route('stations.update', $station), ['name' => 'New', 'currency_id' => $khr->id])
            ->assertRedirect(route('stations.index'));

        $this->assertSame('New', $station->fresh()->name);
    }

    public function test_the_stations_index_exposes_the_business_currency_to_label_inheriting_stations(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $khr = CurrencyFactory::seeded('KHR');
        Station::factory()->for($business)->create(['name' => 'A Inherits']);
        Station::factory()->for($business)->create(['name' => 'B Overrides', 'currency_id' => $khr->id]);

        $this->actingAs($user)
            ->get(route('stations.index'))
            ->assertInertia(fn ($page) => $page
                ->where('business_currency_id', $business->currency_id)
                ->where('stations.0.currency_id', null)
                ->where('stations.1.currency_id', $khr->id));
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
