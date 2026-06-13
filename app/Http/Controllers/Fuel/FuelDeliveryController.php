<?php

namespace App\Http\Controllers\Fuel;

use App\Http\Controllers\Controller;
use App\Http\Requests\FuelDeliveryRequest;
use App\Models\FuelDelivery;
use App\Models\Tank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FuelDeliveryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('fuel/deliveries', [
            'deliveries' => FuelDelivery::query()
                ->with('tank.fuelType')
                ->latest('delivered_at')
                ->limit(100)
                ->get(),
            'tanks' => Tank::query()
                ->with('fuelType')
                ->orderBy('name')
                ->get(['id', 'name', 'fuel_type_id']),
        ]);
    }

    public function store(FuelDeliveryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['total_cost'] = round((float) $data['volume'] * (float) $data['cost_per_unit'], 2);

        DB::transaction(function () use ($data): void {
            $delivery = FuelDelivery::create($data);
            $delivery->tank->increaseVolume((float) $delivery->volume);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delivery recorded and tank topped up.')]);

        return to_route('fuel.deliveries.index');
    }

    public function destroy(FuelDelivery $delivery): RedirectResponse
    {
        $delivery->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delivery deleted.')]);

        return to_route('fuel.deliveries.index');
    }
}
