<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Business;
use App\Models\Currency;
use App\Models\Station;
use App\Models\User;
use Database\Factories\CurrencyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('onboarding.show'))->assertRedirect(route('login'));
    }

    public function test_user_without_business_sees_business_step(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('onboarding/index')
                ->where('defaults.name', $user->name)
                ->where('defaults.email', $user->email));
    }

    public function test_user_with_business_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();
        Business::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_storing_business_creates_business_and_default_station_then_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('onboarding.business.store'), [
            'name' => 'Acme Fuel',
            'phone' => '012 999 999',
            'email' => 'acme@example.com',
            'address' => 'Street 1',
            'currency_id' => CurrencyFactory::seeded('KHR')->id,
            'customers_per_station' => false,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $business = Business::query()->where('name', 'Acme Fuel')->firstOrFail();
        $this->assertSame($user->id, $business->user_id);
        $this->assertSame('KHR', $business->currency->code);

        // Creating the business makes the user an admin member of it.
        $this->assertSame($business->id, $user->fresh()->business_id);
        $this->assertSame(Role::Admin, $user->fresh()->role);

        // A default station is created automatically, copied from the business.
        $station = $business->stations()->firstOrFail();
        $this->assertSame('Acme Fuel', $station->name);

        // It has no currency of its own, so it follows the business's.
        $this->assertNull($station->currency_id);
        $this->assertSame('KHR', $station->effective_currency->code);

        // The new station becomes the active station.
        $response->assertPlainCookie('station_id', (string) $station->id);
    }

    public function test_storing_a_second_business_is_blocked(): void
    {
        $user = User::factory()->create();
        $existing = Business::factory()->for($user)->create();

        $this->actingAs($user)->post(route('onboarding.business.store'), [
            'name' => 'Second Business',
            'currency_id' => CurrencyFactory::seeded('USD')->id,
        ])->assertRedirect(route('onboarding.show', absolute: false));

        $this->assertDatabaseMissing('businesses', ['name' => 'Second Business']);
        $this->assertSame($existing->id, $user->fresh()->business_id);
    }

    public function test_storing_business_requires_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.business.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_storing_business_requires_a_base_currency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.business.store'), ['name' => 'Acme Fuel'])
            ->assertSessionHasErrors('currency_id');

        $this->assertDatabaseMissing('businesses', ['name' => 'Acme Fuel']);
    }

    public function test_the_base_currency_must_be_an_active_currency(): void
    {
        $user = User::factory()->create();
        $retired = Currency::factory()->inactive()->create();

        foreach ([999999, $retired->id] as $currencyId) {
            $this->actingAs($user)
                ->from(route('onboarding.show'))
                ->post(route('onboarding.business.store'), ['name' => 'Acme Fuel', 'currency_id' => $currencyId])
                ->assertSessionHasErrors('currency_id');
        }

        $this->assertDatabaseMissing('businesses', ['name' => 'Acme Fuel']);
    }

    public function test_the_onboarding_page_can_pick_from_the_shared_currencies(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('onboarding.show'))
            ->assertInertia(fn ($page) => $page
                ->component('onboarding/index')
                ->has('currencies', 4)
                ->where('currencies.0.code', 'KHR'));
    }

    public function test_registration_redirects_to_onboarding(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/onboarding');
    }
}
