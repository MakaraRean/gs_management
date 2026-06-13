<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GasStationAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function routeProvider(): array
    {
        return [
            'dashboard' => ['dashboard'],
            'sales index' => ['sales.index'],
            'sales create' => ['sales.create'],
            'fuel tanks' => ['fuel.tanks.index'],
            'fuel pumps' => ['fuel.pumps.index'],
            'fuel types' => ['fuel.types.index'],
            'fuel deliveries' => ['fuel.deliveries.index'],
            'cash flow' => ['cash-flow.index'],
            'analytics' => ['analytics.index'],
            'reports' => ['reports.index'],
        ];
    }

    /**
     * Routes gated by EnsureHasBusiness (directly or via EnsureHasStation).
     *
     * @return array<string, array{0: string}>
     */
    public static function businessGatedRouteProvider(): array
    {
        return [
            'sales index' => ['sales.index'],
            'fuel tanks' => ['fuel.tanks.index'],
            'cash flow' => ['cash-flow.index'],
            'customers' => ['customers.index'],
            'stations' => ['stations.index'],
            'analytics' => ['analytics.index'],
            'reports' => ['reports.index'],
        ];
    }

    /**
     * Routes additionally gated by EnsureHasStation.
     *
     * @return array<string, array{0: string}>
     */
    public static function stationGatedRouteProvider(): array
    {
        return [
            'sales index' => ['sales.index'],
            'fuel tanks' => ['fuel.tanks.index'],
            'fuel pumps' => ['fuel.pumps.index'],
            'fuel types' => ['fuel.types.index'],
            'fuel deliveries' => ['fuel.deliveries.index'],
            'cash flow' => ['cash-flow.index'],
            'customers' => ['customers.index'],
        ];
    }

    #[DataProvider('routeProvider')]
    public function test_guests_are_redirected_to_login(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    #[DataProvider('routeProvider')]
    public function test_authenticated_users_with_business_and_station_can_access(string $routeName): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($user))->create();

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route($routeName))
            ->assertOk();
    }

    #[DataProvider('businessGatedRouteProvider')]
    public function test_users_without_business_are_redirected_to_dashboard(string $routeName): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_without_business_see_locked_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('dashboard/locked'));
    }

    #[DataProvider('stationGatedRouteProvider')]
    public function test_users_with_business_but_no_station_are_redirected(string $routeName): void
    {
        $user = User::factory()->create();
        Business::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_with_business_but_no_station_can_access_business_only_routes(): void
    {
        $user = User::factory()->create();
        Business::factory()->for($user)->create();

        foreach (['stations.index', 'analytics.index', 'reports.index', 'dashboard'] as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk();
        }
    }
}
