<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_makes_owners_admins_and_other_members_cashiers(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $member = User::factory()->create(['business_id' => $business->id]);
        $removedMember = User::factory()->create(['business_id' => $business->id, 'is_active' => false]);
        $userWithoutBusiness = User::factory()->create();

        $migration = require database_path('migrations/2026_10_29_185610_add_role_to_users_table.php');
        $migration->down();
        $migration->up();

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['id' => $member->id, 'role' => 'cashier']);
        $this->assertDatabaseHas('users', ['id' => $removedMember->id, 'role' => 'cashier']);
        $this->assertDatabaseHas('users', ['id' => $userWithoutBusiness->id, 'role' => null]);
    }
}
