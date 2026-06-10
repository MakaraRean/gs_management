<?php

namespace App\Http\Controllers\Fuel;

use App\Http\Controllers\Controller;
use App\Http\Requests\TankRequest;
use App\Models\FuelType;
use App\Models\Tank;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TankController extends Controller
{
    public function index(): Response
    {
        $tanks = Tank::query()
            ->with('fuelType')
            ->withCount('pumps')
            ->orderBy('name')
            ->get()
            ->map(fn (Tank $tank): array => [
                ...$tank->toArray(),
                'fill_percentage' => $tank->fillPercentage(),
            ]);

        return Inertia::render('fuel/tanks', [
            'tanks' => $tanks,
            'fuelTypes' => FuelType::query()->orderBy('name')->get(['id', 'name', 'unit']),
        ]);
    }

    public function store(TankRequest $request): RedirectResponse
    {
        Tank::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tank created.')]);

        return to_route('fuel.tanks.index');
    }

    public function update(TankRequest $request, Tank $tank): RedirectResponse
    {
        $tank->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tank updated.')]);

        return to_route('fuel.tanks.index');
    }

    public function destroy(Tank $tank): RedirectResponse
    {
        $tank->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tank deleted.')]);

        return to_route('fuel.tanks.index');
    }
}
