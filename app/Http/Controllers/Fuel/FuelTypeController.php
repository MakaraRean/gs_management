<?php

namespace App\Http\Controllers\Fuel;

use App\Http\Controllers\Controller;
use App\Http\Requests\FuelTypeRequest;
use App\Models\FuelType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FuelTypeController extends Controller
{
    public function index(): Response
    {
        $station = $this->currentStation();

        return Inertia::render('fuel/types', [
            'fuelTypes' => FuelType::query()
                ->where('station_id', $station->id)
                ->withCount('tanks')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(FuelTypeRequest $request): RedirectResponse
    {
        FuelType::create([
            ...$request->validated(),
            'station_id' => $this->currentStation()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fuel type created.')]);

        return to_route('fuel.types.index');
    }

    public function update(FuelTypeRequest $request, FuelType $fuelType): RedirectResponse
    {
        abort_unless($fuelType->station_id === $this->currentStation()->id, 403);

        $fuelType->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fuel type updated.')]);

        return to_route('fuel.types.index');
    }

    public function destroy(FuelType $fuelType): RedirectResponse
    {
        abort_unless($fuelType->station_id === $this->currentStation()->id, 403);

        $fuelType->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fuel type deleted.')]);

        return to_route('fuel.types.index');
    }
}
