<?php

namespace App\Http\Controllers\Fuel;

use App\Http\Controllers\Controller;
use App\Http\Requests\FuelDeliveryRequest;
use App\Models\FuelDelivery;
use App\Models\Tank;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FuelDeliveryController extends Controller
{
    public function index(): Response
    {
        $station = $this->currentStation();

        return Inertia::render('fuel/deliveries', [
            'deliveries' => FuelDelivery::query()
                ->forStation($station)
                ->with(['tank' => fn ($query) => $query->withInactive()->with('fuelType')])
                ->latest('delivered_at')
                ->limit(100)
                ->get(),
            'tanks' => Tank::query()
                ->where('station_id', $station->id)
                ->with('fuelType')
                ->orderBy('name')
                ->get(['id', 'name', 'fuel_type_id']),
        ]);
    }

    public function store(FuelDeliveryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        abort_unless(
            Tank::query()->whereKey($data['tank_id'])->where('station_id', $this->currentStation()->id)->exists(),
            403,
        );

        $currency = $this->currentCurrency();

        $data['cost_per_unit'] = $currency->round($data['cost_per_unit']);
        $data['total_cost'] = $currency->round((float) $data['volume'] * $data['cost_per_unit']);
        $data['delivered_at'] = LocalTime::toUtc($data['delivered_at']);

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
