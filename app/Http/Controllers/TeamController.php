<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $this->currentBusiness();
        $canManageTeam = $request->user()->hasPermission(Permission::ManageTeam);

        $members = $business->members()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'business_id'])
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                // The owner is always an admin, whatever role is stored.
                'role' => $member->id === $business->user_id ? Role::Admin : $member->role,
                'is_owner' => $member->id === $business->user_id,
                'can_manage' => $canManageTeam
                    && $member->id !== $business->user_id
                    && $member->id !== $request->user()->id,
            ]);

        return Inertia::render('team/index', [
            'business' => $business->only(['id', 'name', 'phone', 'email', 'address', 'customers_per_station']),
            'members' => $members,
            'roles' => Role::cases(),
            'canManageTeam' => $canManageTeam,
        ]);
    }
}
