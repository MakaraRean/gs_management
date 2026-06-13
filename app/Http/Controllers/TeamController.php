<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $this->currentBusiness();

        $members = $business->members()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'business_id'])
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'is_owner' => $member->id === $business->user_id,
            ]);

        return Inertia::render('team/index', [
            'business' => $business->only(['id', 'name', 'phone', 'email', 'address', 'customers_per_station']),
            'members' => $members,
            'isOwner' => $business->user_id === $request->user()->id,
        ]);
    }
}
