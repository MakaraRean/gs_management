<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BusinessUserController extends Controller
{
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $business = $this->authorizeOwner($request);

        User::create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'business_id' => $business->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.user_added')]);

        return to_route('team.index');
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $business = $this->authorizeOwner($request);

        abort_unless($member->business_id === $business->id, 403);
        abort_if($member->id === $business->user_id, 403, __('team.cannot_remove_owner'));
        abort_if($member->id === $request->user()->id, 403);

        $member->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.user_removed')]);

        return to_route('team.index');
    }

    private function authorizeOwner(Request $request): Business
    {
        $business = $this->currentBusiness();

        abort_unless($business->user_id === $request->user()->id, 403);

        return $business;
    }
}
