<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StationSwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'station_id' => ['required', 'integer', 'exists:stations,id'],
        ]);

        $station = Station::query()
            ->where('business_id', $request->user()->business_id)
            ->findOrFail($validated['station_id']);

        abort_unless($station->is_active, 422, __('stations.inactive'));

        return back()->withCookie(cookie()->forever('station_id', $station->id));
    }
}
