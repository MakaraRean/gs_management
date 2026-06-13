<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->business) {
            return to_route('dashboard');
        }

        return Inertia::render('onboarding/index');
    }

    public function storeBusiness(BusinessRequest $request): RedirectResponse
    {
        // A user belongs to exactly one business; if they already have one, stop here.
        if ($request->user()->business_id) {
            return to_route('onboarding.show');
        }

        $business = $request->user()->businesses()->create($request->validated());
        $request->user()->update(['business_id' => $business->id]);

        // Every business starts with a default station, copied from its own details.
        $station = $business->stations()->create([
            'name' => $business->name,
            'phone' => $business->phone,
            'email' => $business->email,
            'address' => $business->address,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('onboarding.completed')]);

        return to_route('dashboard')->withCookie(cookie()->forever('station_id', $station->id));
    }
}
