<?php

namespace App\Http\Controllers\Fuel;

use App\Http\Controllers\Controller;
use App\Http\Requests\PumpRequest;
use App\Models\Pump;
use App\Models\Tank;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PumpController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('fuel/pumps', [
            'pumps' => Pump::query()
                ->with('tank.fuelType')
                ->orderBy('name')
                ->get(),
            'tanks' => Tank::query()
                ->with('fuelType')
                ->orderBy('name')
                ->get(['id', 'name', 'fuel_type_id']),
        ]);
    }

    public function store(PumpRequest $request): RedirectResponse
    {
        Pump::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pump created.')]);

        return to_route('fuel.pumps.index');
    }

    public function update(PumpRequest $request, Pump $pump): RedirectResponse
    {
        $pump->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pump updated.')]);

        return to_route('fuel.pumps.index');
    }

    public function destroy(Pump $pump): RedirectResponse
    {
        $pump->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pump deleted.')]);

        return to_route('fuel.pumps.index');
    }
}
