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
        $station = $this->currentStation();

        $tanks = Tank::query()
            ->where('station_id', $station->id)
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
            'fuelTypes' => FuelType::query()
                ->where('station_id', $station->id)
                ->orderBy('name')
                ->get(['id', 'name', 'unit']),
        ]);
    }

    public function store(TankRequest $request): RedirectResponse
    {
        Tank::create([
            ...$request->validated(),
            'station_id' => $this->currentStation()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tank created.')]);

        return to_route('fuel.tanks.index');
    }

    public function update(TankRequest $request, Tank $tank): RedirectResponse
    {
        abort_unless($tank->station_id === $this->currentStation()->id, 403);

        $tank->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tank updated.')]);

        return to_route('fuel.tanks.index');
    }

    public function destroy(Tank $tank): RedirectResponse
    {
        abort_unless($tank->station_id === $this->currentStation()->id, 403);

        $tank->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tank deleted.')]);

        return to_route('fuel.tanks.index');
    }
}
