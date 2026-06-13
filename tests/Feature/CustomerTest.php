<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Business;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Sale;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_customers(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
    }

    public function test_a_customer_can_be_created(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('customers.store'), [
                'name' => 'Dara Sok',
                'phone' => '012345678',
                'email' => 'dara@example.com',
                'credit_limit' => 1000,
            ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Dara Sok',
            'business_id' => $station->business_id,
            'station_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_customer_can_be_assigned_to_specific_station(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('customers.store'), [
                'name' => 'Station Customer',
                'credit_limit' => 500,
                'station_id' => $station->id,
            ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Station Customer',
            'business_id' => $business->id,
            'station_id' => $station->id,
        ]);
    }

    public function test_customer_cannot_be_assigned_to_station_of_different_business(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $otherStation = Station::factory()->create(); // belongs to a different business

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('customers.store'), [
                'name' => 'Cross Business',
                'credit_limit' => 500,
                'station_id' => $otherStation->id,
            ])->assertForbidden();
    }

    public function test_a_customer_can_be_updated(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $customer = Customer::factory()->create(['name' => 'Old Name', 'business_id' => $business->id]);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->put(route('customers.update', $customer), [
                'name' => 'New Name',
                'credit_limit' => 2000,
            ])->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'New Name']);
    }

    public function test_destroying_a_customer_sets_is_active_false(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $customer = Customer::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => false]);
    }

    public function test_inactive_customers_are_excluded_from_index(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);

        Customer::factory()->create(['name' => 'Active', 'business_id' => $business->id]);
        Customer::factory()->create(['name' => 'Inactive', 'business_id' => $business->id, 'is_active' => false]);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('customers.index'))
            ->assertInertia(fn ($page) => $page->has('customers', 1));
    }

    public function test_an_address_can_be_linked_to_a_customer(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);

        $this->actingAs($user)->post(route('customers.addresses.store', $customer), [
            'street' => '123 Main St',
            'city' => 'Phnom Penh',
            'province' => 'Phnom Penh',
            'country' => 'KH',
            'label' => 'home',
            'is_default' => true,
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('addresses', ['street' => '123 Main St']);
        $this->assertDatabaseHas('customer_address', [
            'customer_id' => $customer->id,
            'label' => 'home',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_unlinking_an_address_sets_pivot_is_active_false(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);
        $address = Address::factory()->create();
        $customer->addresses()->attach($address->id, ['label' => 'home', 'is_default' => false, 'is_active' => true]);

        $this->actingAs($user)
            ->delete(route('customers.addresses.destroy', [$customer, $address]))
            ->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customer_address', [
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_outstanding_balance_is_sum_of_credits_minus_payments(): void
    {
        $customer = Customer::factory()->create();
        $fuelType = FuelType::factory()->create(['unit_price' => 2.0]);
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id, 'capacity' => 1000, 'current_volume' => 1000]);
        $pump = Pump::factory()->create(['tank_id' => $tank->id]);

        Sale::factory()->create([
            'pump_id' => $pump->id,
            'fuel_type_id' => $fuelType->id,
            'customer_id' => $customer->id,
            'payment_method' => 'credit',
            'total_amount' => 300.00,
        ]);
        Sale::factory()->create([
            'pump_id' => $pump->id,
            'fuel_type_id' => $fuelType->id,
            'customer_id' => $customer->id,
            'payment_method' => 'credit',
            'total_amount' => 150.00,
        ]);

        $customer->payments()->create([
            'amount' => 100.00,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->assertEquals(350.00, $customer->outstandingBalance());
    }
}
