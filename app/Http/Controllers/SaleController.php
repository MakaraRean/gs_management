<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\FuelType;
use App\Models\Pump;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $station = $this->currentStation();

        $filters = [
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
            'fuel_type_id' => $request->integer('fuel_type_id') ?: null,
        ];

        $sales = Sale::query()
            ->where('station_id', $station->id)
            ->with(['pump:id,name', 'fuelType:id,name,unit', 'user:id,name'])
            ->when($filters['from'], fn ($query, $from) => $query->whereDate('sold_at', '>=', $from))
            ->when($filters['to'], fn ($query, $to) => $query->whereDate('sold_at', '<=', $to))
            ->when($filters['fuel_type_id'], fn ($query, $id) => $query->where('fuel_type_id', $id))
            ->latest('sold_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('sales/index', [
            'sales' => $sales,
            'filters' => $filters,
            'fuelTypes' => FuelType::query()
                ->where('station_id', $station->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $station = $this->currentStation();
        $business = $this->currentBusiness();

        $customers = Customer::query()
            ->where('business_id', $business->id)
            ->where(fn ($q) => $q->whereNull('station_id')->orWhere('station_id', $station->id))
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return Inertia::render('sales/create', [
            'pumps' => Pump::query()
                ->where('status', 'active')
                ->whereHas('tank', fn ($q) => $q->where('station_id', $station->id))
                ->with('tank.fuelType')
                ->orderBy('name')
                ->get()
                ->map(fn (Pump $pump): array => [
                    'id' => $pump->id,
                    'name' => $pump->name,
                    'fuel_type_id' => $pump->tank->fuel_type_id,
                    'fuel_type_name' => $pump->tank->fuelType->name,
                    'unit' => $pump->tank->fuelType->unit,
                    'unit_price' => (float) $pump->tank->fuelType->unit_price,
                    'available_volume' => (float) $pump->tank->current_volume,
                ]),
            'customers' => $customers,
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $pump = Pump::with('tank.fuelType')->findOrFail($request->integer('pump_id'));
        $volume = (float) $request->input('volume');
        $unitPrice = (float) $pump->tank->fuelType->unit_price;

        abort_unless($pump->tank->station_id === $this->currentStation()->id, 403);

        DB::transaction(function () use ($request, $pump, $volume, $unitPrice): void {
            Sale::create([
                'pump_id' => $pump->id,
                'fuel_type_id' => $pump->tank->fuel_type_id,
                'user_id' => $request->user()?->id,
                'station_id' => $pump->tank->station_id,
                'customer_id' => $request->input('customer_id'),
                'volume' => $volume,
                'unit_price' => $unitPrice,
                'total_amount' => round($volume * $unitPrice, 2),
                'payment_method' => $request->input('payment_method'),
                'sold_at' => $request->date('sold_at') ?? Carbon::now(),
                'notes' => $request->input('notes'),
            ]);

            $pump->tank->decreaseVolume($volume);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sale recorded.')]);

        return to_route('sales.index');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        abort_unless($sale->station_id === $this->currentStation()->id, 403);

        $sale->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sale deleted.')]);

        return to_route('sales.index');
    }
}
