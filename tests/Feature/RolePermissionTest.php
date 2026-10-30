<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Business;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pages a member can open, by route name: [cashier allowed, gas jockey allowed].
     */
    private const ROUTES = [
        'dashboard' => [true, true],
        'team.index' => [true, true],
        'sales.index' => [true, true],
        'sales.create' => [true, true],
        'cash-flow.index' => [true, false],
        'analytics.index' => [true, false],
        'customers.index' => [true, false],
        'fuel.tanks.index' => [false, false],
        'fuel.pumps.index' => [false, false],
        'fuel.types.index' => [false, false],
        'fuel.deliveries.index' => [false, false],
        'reports.index' => [false, false],
        'stations.index' => [false, false],
        'deletion-requests.index' => [false, false],
    ];

    /**
     * @return array<string, array{0: Role, 1: string, 2: bool}>
     */
    public static function staffRouteProvider(): array
    {
        $cases = [];

        foreach (self::ROUTES as $routeName => [$cashierAllowed, $gasJockeyAllowed]) {
            $cases["cashier {$routeName}"] = [Role::Cashier, $routeName, $cashierAllowed];
            $cases["gas jockey {$routeName}"] = [Role::GasJockey, $routeName, $gasJockeyAllowed];
        }

        return $cases;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminRouteProvider(): array
    {
        $cases = [];

        foreach (array_keys(self::ROUTES) as $routeName) {
            $cases[$routeName] = [$routeName];
        }

        return $cases;
    }

    /**
     * @return array<string, array{0: Role, 1: list<string>}>
     */
    public static function sharedPermissionsProvider(): array
    {
        return [
            'admin' => [Role::Admin, [
                'sales',
                'fuel_management',
                'cash_flow',
                'sales_analysis',
                'reports',
                'stations',
                'manage_team',
                'customers',
                'delete_records',
            ]],
            'cashier' => [Role::Cashier, ['sales', 'cash_flow', 'sales_analysis', 'customers']],
            'gas jockey' => [Role::GasJockey, ['sales']],
        ];
    }

    #[DataProvider('staffRouteProvider')]
    public function test_staff_roles_only_reach_their_modules(Role $role, string $routeName, bool $allowed): void
    {
        [$member, $station] = $this->memberWithStation($role);

        $this->actingAs($member)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route($routeName))
            ->assertStatus($allowed ? 200 : 403);
    }

    #[DataProvider('adminRouteProvider')]
    public function test_admins_who_are_not_the_owner_reach_every_module(string $routeName): void
    {
        [$admin, $station] = $this->memberWithStation(Role::Admin);

        $this->actingAs($admin)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_cashier_cannot_create_fuel_tanks(): void
    {
        [$cashier, $station] = $this->memberWithStation(Role::Cashier);
        $fuelType = FuelType::factory()->create(['station_id' => $station->id]);

        $this->actingAs($cashier)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('fuel.tanks.store'), [
                'name' => 'Tank X',
                'fuel_type_id' => $fuelType->id,
                'capacity' => 1000,
                'current_volume' => 0,
            ])->assertForbidden();

        $this->assertDatabaseMissing('tanks', ['name' => 'Tank X']);
    }

    public function test_gas_jockey_can_record_a_sale(): void
    {
        [$gasJockey, $station] = $this->memberWithStation(Role::GasJockey);
        $pump = $this->pumpWithVolume($station);

        $this->actingAs($gasJockey)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('sales.store'), [
                'pump_id' => $pump->id,
                'volume' => 10,
                'payment_method' => 'cash',
            ])->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sales', ['pump_id' => $pump->id, 'user_id' => $gasJockey->id]);
    }

    public function test_gas_jockey_cannot_create_customers(): void
    {
        [$gasJockey, $station] = $this->memberWithStation(Role::GasJockey);

        $this->actingAs($gasJockey)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('customers.store'), ['name' => 'Walk-in'])
            ->assertForbidden();

        $this->assertDatabaseMissing('customers', ['name' => 'Walk-in']);
    }

    /**
     * @param  list<string>  $permissions
     */
    #[DataProvider('sharedPermissionsProvider')]
    public function test_role_and_permissions_are_shared_with_the_frontend(Role $role, array $permissions): void
    {
        [$member, $station] = $this->memberWithStation($role);

        $this->actingAs($member)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.role', $role->value)
                ->where('auth.permissions', $permissions));
    }

    public function test_users_without_a_business_have_no_permissions(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('auth.permissions', []));
    }

    public function test_members_without_a_role_only_reach_the_dashboard_and_team(): void
    {
        $station = Station::factory()->for(Business::factory()->for(User::factory()))->create();
        $member = User::factory()->create(['business_id' => $station->business_id]);

        $this->actingAs($member)->withUnencryptedCookie('station_id', $station->id);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auth.permissions', []));
        $this->get(route('team.index'))->assertOk();
        $this->get(route('sales.index'))->assertForbidden();
    }

    public function test_the_owner_keeps_every_permission_without_a_stored_role(): void
    {
        $owner = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($owner))->create();
        $owner->forceFill(['role' => null])->save();

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('stations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.permissions', array_column(Permission::cases(), 'value')));
    }

    public function test_members_of_a_business_without_stations_are_told_who_can_add_one(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $cashier = User::factory()->cashier()->create(['business_id' => $business->id]);

        $this->actingAs($cashier)
            ->withUnencryptedCookie('locale', 'en')
            ->get(route('sales.index'))
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertInertiaFlash('toast.message', 'This feature needs a station. Ask an admin to add one.');

        $this->actingAs($owner)
            ->withUnencryptedCookie('locale', 'en')
            ->get(route('sales.index'))
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertInertiaFlash('toast.message', 'Create a station first to use this feature.');
    }

    /**
     * A member with the given role at a business that has a station.
     *
     * @return array{0: User, 1: Station}
     */
    private function memberWithStation(Role $role): array
    {
        $station = Station::factory()->for(Business::factory()->for(User::factory()))->create();
        $member = User::factory()->state(['role' => $role])->create(['business_id' => $station->business_id]);

        return [$member, $station];
    }

    private function pumpWithVolume(Station $station): Pump
    {
        $fuelType = FuelType::factory()->create(['unit_price' => 1.5, 'station_id' => $station->id]);
        $tank = Tank::factory()->create([
            'fuel_type_id' => $fuelType->id,
            'station_id' => $station->id,
            'capacity' => 1000,
            'current_volume' => 500,
        ]);

        return Pump::factory()->create(['tank_id' => $tank->id, 'status' => 'active']);
    }
}
