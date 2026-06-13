<?php

namespace App\Http\Controllers;

use App\Http\Requests\StationRequest;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('stations/index', [
            'stations' => Station::query()
                ->where('business_id', $request->user()->business_id)
                ->withCount('tanks')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StationRequest $request): RedirectResponse
    {
        Station::create([
            ...$request->validated(),
            'business_id' => $request->user()->business_id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('stations.created')]);

        return to_route('stations.index');
    }

    public function update(StationRequest $request, Station $station): RedirectResponse
    {
        $this->authorizeStation($request, $station);

        $station->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('stations.updated')]);

        return to_route('stations.index');
    }

    public function destroy(Request $request, Station $station): RedirectResponse
    {
        $this->authorizeStation($request, $station);

        $station->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('stations.deleted')]);

        return to_route('stations.index');
    }

    private function authorizeStation(Request $request, Station $station): void
    {
        abort_unless(
            $station->business_id === $request->user()->business_id,
            403
        );
    }
}
