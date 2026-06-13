<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_index_lists_members_with_owner_flag(): void
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $business = Business::factory()->for($owner)->create();
        User::factory()->create(['name' => 'Staff', 'business_id' => $business->id]);

        $this->actingAs($owner)
            ->get(route('team.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('team/index')
                ->where('isOwner', true)
                ->has('members', 2));
    }

    public function test_owner_can_add_a_member(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->post(route('team.members.store'), [
                'name' => 'New Staff',
                'email' => 'staff@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertRedirect(route('team.index'));

        $member = User::query()->where('email', 'staff@example.com')->firstOrFail();
        $this->assertSame($business->id, $member->business_id);
        $this->assertTrue(Hash::check('secret-password', $member->password));
    }

    public function test_added_member_is_scoped_to_the_same_business(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $station = Station::factory()->create(['business_id' => $business->id]);
        $member = User::factory()->create(['business_id' => $business->id]);

        $this->actingAs($member)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('stations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('stations/index')
                ->has('stations', 1)
                ->where('stations.0.id', $station->id));
    }

    public function test_non_owner_cannot_add_a_member(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $member = User::factory()->create(['business_id' => $business->id]);

        $this->actingAs($member)
            ->post(route('team.members.store'), [
                'name' => 'Another',
                'email' => 'another@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'another@example.com']);
    }

    public function test_owner_can_remove_a_member(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create();
        $member = User::factory()->create(['business_id' => $business->id]);

        $this->actingAs($owner)
            ->delete(route('team.members.destroy', $member))
            ->assertRedirect(route('team.index'));

        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => false]);
    }

    public function test_owner_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        Business::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->delete(route('team.members.destroy', $owner))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'is_active' => true]);
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
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertSessionHasErrors('email');
    }
}
