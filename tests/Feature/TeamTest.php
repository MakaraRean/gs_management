<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Business;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: Role}>
     */
    public static function staffRoleProvider(): array
    {
        return [
            'cashier' => [Role::Cashier],
            'gas jockey' => [Role::GasJockey],
        ];
    }

    public function test_team_index_lists_members_with_owner_flag_and_roles(): void
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $business = Business::factory()->for($owner)->create();
        User::factory()->cashier()->create(['name' => 'Staff', 'business_id' => $business->id]);

        $this->actingAs($owner)
            ->get(route('team.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('team/index')
                ->where('canManageTeam', true)
                ->where('roles', ['admin', 'cashier', 'gas_jockey'])
                ->has('members', 2)
                ->where('members.0.name', 'Owner')
                ->where('members.0.role', 'admin')
                ->where('members.0.is_owner', true)
                ->where('members.0.can_manage', false)
                ->where('members.1.name', 'Staff')
                ->where('members.1.role', 'cashier')
                ->where('members.1.can_manage', true));
    }

    #[DataProvider('staffRoleProvider')]
    public function test_staff_can_view_the_team_without_management_controls(Role $role): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $staff = User::factory()->state(['role' => $role])->create(['business_id' => $business->id]);

        $this->actingAs($staff)
            ->get(route('team.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('team/index')
                ->where('canManageTeam', false)
                ->has('members', 2)
                ->where('members.0.can_manage', false)
                ->where('members.1.can_manage', false));
    }

    public function test_owner_can_add_a_member_with_a_role(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->post(route('team.members.store'), [
                'name' => 'New Staff',
                'email' => 'staff@example.com',
                'role' => 'gas_jockey',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertRedirect(route('team.index'));

        $member = User::query()->where('email', 'staff@example.com')->firstOrFail();
        $this->assertSame($business->id, $member->business_id);
        $this->assertSame(Role::GasJockey, $member->role);
        $this->assertTrue(Hash::check('secret-password', $member->password));
    }

    public function test_adding_a_member_requires_a_valid_role(): void
    {
        $owner = User::factory()->create();
        Business::factory()->for($owner)->create();

        $member = [
            'name' => 'New Staff',
            'email' => 'staff@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ];

        $this->actingAs($owner)
            ->from(route('team.index'))
            ->post(route('team.members.store'), $member)
            ->assertSessionHasErrors('role');

        $this->actingAs($owner)
            ->from(route('team.index'))
            ->post(route('team.members.store'), [...$member, 'role' => 'owner'])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);
    }

    public function test_added_member_is_scoped_to_the_same_business(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $member = User::factory()->admin()->create(['business_id' => $business->id]);

        $this->actingAs($member)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('stations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('stations/index')
                ->has('stations', 1)
                ->where('stations.0.id', $station->id));
    }

    #[DataProvider('staffRoleProvider')]
    public function test_staff_cannot_manage_the_team(Role $role): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create(['name' => 'Original Fuel']);
        $staff = User::factory()->state(['role' => $role])->create(['business_id' => $business->id]);
        $colleague = User::factory()->gasJockey()->create(['business_id' => $business->id]);

        $this->actingAs($staff)
            ->post(route('team.members.store'), [
                'name' => 'Another',
                'email' => 'another@example.com',
                'role' => 'admin',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('team.members.update', $colleague), ['role' => 'admin'])
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('team.members.destroy', $colleague))
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('business.update'), ['name' => 'Renamed Fuel'])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'another@example.com']);
        $this->assertDatabaseHas('users', ['id' => $colleague->id, 'role' => 'gas_jockey', 'is_active' => true]);
        $this->assertSame('Original Fuel', $business->fresh()->name);
    }

    public function test_admin_who_is_not_the_owner_can_manage_the_team(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create(['business_id' => $business->id]);
        $cashier = User::factory()->cashier()->create(['business_id' => $business->id]);
        $leaver = User::factory()->gasJockey()->create(['business_id' => $business->id]);

        $this->actingAs($admin)
            ->post(route('team.members.store'), [
                'name' => 'New Cashier',
                'email' => 'new-cashier@example.com',
                'role' => 'cashier',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertRedirect(route('team.index'));

        $this->actingAs($admin)
            ->patch(route('team.members.update', $cashier), ['role' => 'admin'])
            ->assertRedirect(route('team.index'));

        $this->actingAs($admin)
            ->delete(route('team.members.destroy', $leaver))
            ->assertRedirect(route('team.index'));

        $this->actingAs($admin)
            ->put(route('business.update'), ['name' => 'Renamed Fuel'])
            ->assertRedirect(route('team.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-cashier@example.com',
            'business_id' => $business->id,
            'role' => 'cashier',
        ]);
        $this->assertSame(Role::Admin, $cashier->fresh()->role);
        $this->assertDatabaseHas('users', ['id' => $leaver->id, 'is_active' => false]);
        $this->assertSame('Renamed Fuel', $business->fresh()->name);
    }

    public function test_changing_a_role_requires_a_valid_role(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $cashier = User::factory()->cashier()->create(['business_id' => $business->id]);

        $this->actingAs($owner)
            ->from(route('team.index'))
            ->patch(route('team.members.update', $cashier), ['role' => 'owner'])
            ->assertSessionHasErrors('role');

        $this->assertSame(Role::Cashier, $cashier->fresh()->role);
    }

    public function test_owner_role_cannot_be_changed(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create(['business_id' => $business->id]);

        $this->actingAs($admin)
            ->patch(route('team.members.update', $owner), ['role' => 'cashier'])
            ->assertForbidden();

        $this->assertSame(Role::Admin, $owner->fresh()->role);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create(['business_id' => $business->id]);

        $this->actingAs($admin)
            ->patch(route('team.members.update', $admin), ['role' => 'cashier'])
            ->assertForbidden();

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_member_of_another_business_cannot_have_their_role_changed(): void
    {
        $owner = User::factory()->create();
        Business::factory()->for($owner)->create();
        $foreign = User::factory()->cashier()->create(['business_id' => Business::factory()->create()->id]);

        $this->actingAs($owner)
            ->patch(route('team.members.update', $foreign), ['role' => 'admin'])
            ->assertForbidden();

        $this->assertSame(Role::Cashier, $foreign->fresh()->role);
    }

    public function test_owner_can_remove_a_member(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $member = User::factory()->cashier()->create(['business_id' => $business->id]);

        $this->actingAs($owner)
            ->delete(route('team.members.destroy', $member))
            ->assertRedirect(route('team.index'));

        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => false]);
    }

    public function test_owner_is_listed_as_admin_even_without_a_stored_role(): void
    {
        $owner = User::factory()->create();
        Business::factory()->for($owner)->create();
        $owner->forceFill(['role' => null])->save();

        $this->actingAs($owner)
            ->get(route('team.index'))
            ->assertInertia(fn ($page) => $page
                ->where('members.0.is_owner', true)
                ->where('members.0.role', 'admin'));
    }

    public function test_owner_cannot_remove_themselves(): void
    {
        $owner = User::factory()->create();
        Business::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->delete(route('team.members.destroy', $owner))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'is_active' => true]);
    }

    public function test_admin_who_is_not_the_owner_cannot_remove_the_owner_or_themselves(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create(['business_id' => $business->id]);

        $this->actingAs($admin)
            ->delete(route('team.members.destroy', $owner))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('team.members.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'is_active' => true]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }

    public function test_admin_can_remove_another_admin(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create(['business_id' => $business->id]);
        $otherAdmin = User::factory()->admin()->create(['business_id' => $business->id]);

        $this->actingAs($admin)
            ->delete(route('team.members.destroy', $otherAdmin))
            ->assertRedirect(route('team.index'));

        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id, 'is_active' => false]);
    }

    public function test_admin_who_is_not_the_owner_can_manage_everyone_except_the_owner_and_themselves(): void
    {
        $owner = User::factory()->create(['name' => 'A Owner']);
        $business = Business::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create(['name' => 'B Admin', 'business_id' => $business->id]);
        User::factory()->cashier()->create(['name' => 'C Cashier', 'business_id' => $business->id]);

        $this->actingAs($admin)
            ->get(route('team.index'))
            ->assertInertia(fn ($page) => $page
                ->where('canManageTeam', true)
                ->where('members.0.name', 'A Owner')
                ->where('members.0.can_manage', false)
                ->where('members.1.name', 'B Admin')
                ->where('members.1.can_manage', false)
                ->where('members.2.name', 'C Cashier')
                ->where('members.2.can_manage', true));
    }

    public function test_member_of_another_business_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        Business::factory()->for($owner)->create();
        $foreign = User::factory()->create(['business_id' => Business::factory()->create()->id]);

        $this->actingAs($owner)
            ->delete(route('team.members.destroy', $foreign))
            ->assertForbidden();
    }

    public function test_adding_a_member_with_duplicate_email_fails(): void
    {
        $owner = User::factory()->create();
        Business::factory()->for($owner)->create();
        $existing = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($owner)
            ->from(route('team.index'))
            ->post(route('team.members.store'), [
                'name' => 'Dupe',
                'email' => $existing->email,
                'role' => 'cashier',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertSessionHasErrors('email');
    }
}
