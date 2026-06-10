<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    #[\PHPUnit\Framework\Attributes\DataProvider('routeProvider')]
    public function test_guests_are_redirected_to_login(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('routeProvider')]
    public function test_authenticated_users_can_access(string $routeName): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route($routeName))->assertOk();
    }
}
