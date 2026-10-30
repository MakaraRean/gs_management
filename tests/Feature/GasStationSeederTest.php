<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\GasStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GasStationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_leaves_other_accounts_alone_and_can_run_again_after_demo_staff_were_removed(): void
    {
        $realOwner = User::factory()->create();
        $realBusiness = Business::factory()->for($realOwner)->create();

        $this->seed(GasStationSeeder::class);

        $demoOwner = User::query()->where('email', 'test@example.com')->sole();
        $this->assertSame(Role::Admin, $demoOwner->role);
        $this->assertSame($realBusiness->id, $realOwner->fresh()->business_id);

        User::query()->where('email', 'jockey@example.com')->sole()->deactivate();

        $this->seed(GasStationSeeder::class);

        $demoOwner->refresh();
        $this->assertDatabaseHas('users', [
            'email' => 'jockey@example.com',
            'business_id' => $demoOwner->business_id,
            'role' => 'gas_jockey',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'cashier@example.com',
            'business_id' => $demoOwner->business_id,
            'role' => 'cashier',
        ]);
    }
}
