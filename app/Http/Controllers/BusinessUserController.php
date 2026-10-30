<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BusinessUserController extends Controller
{
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $business = $this->currentBusiness();

        User::create([
            ...$request->safe()->only(['name', 'email', 'password', 'role']),
            'business_id' => $business->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.user_added')]);

        return to_route('team.index');
    }

    public function update(UpdateMemberRoleRequest $request, User $member): RedirectResponse
    {
        $business = $this->currentBusiness();

        abort_unless($member->business_id === $business->id, 403);
        abort_if($member->id === $business->user_id, 403, __('team.cannot_change_owner_role'));
        abort_if($member->id === $request->user()->id, 403);

        $member->update(['role' => $request->validated('role')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.role_updated')]);

        return to_route('team.index');
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $business = $this->currentBusiness();

        abort_unless($member->business_id === $business->id, 403);
        abort_if($member->id === $business->user_id, 403, __('team.cannot_remove_owner'));
        abort_if($member->id === $request->user()->id, 403);

        $member->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.user_removed')]);

        return to_route('team.index');
    }
}
