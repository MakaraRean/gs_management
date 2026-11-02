<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\FuelDelivery;
use App\Models\Sale;
use App\Models\Tank;
use App\Support\LocalTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->business_id) {
            return Inertia::render('dashboard/locked');
        }

        // A business whose stations are all gone has nothing to total yet.
        if (! app()->bound('current.station')) {
            return Inertia::render('dashboard', [
                'stats' => ['today_revenue' => 0, 'today_volume' => 0, 'today_transactions' => 0, 'net_cash' => 0],
                'tanks' => [],
                'recentSales' => [],
                'revenueByDay' => [],
            ]);
        }

        $station = $this->currentStation();
        $currency = $this->currentCurrency();
        $today = LocalTime::today();

        // Everything is the active station's, in its current currency, so no total mixes currencies.
        $sales = fn () => Sale::query()->where('station_id', $station->id)->inCurrency($currency);

        $todaySales = $sales()->whereBetween('sold_at', LocalTime::dayRange($today, $today));

        $cashSales = (float) $sales()->where('payment_method', 'cash')->sum('total_amount');
        $deliveries = (float) FuelDelivery::query()
            ->whereHas('tank', fn ($query) => $query->where('station_id', $station->id))
            ->inCurrency($currency)
            ->sum('total_cost');
        $manualCashFlows = fn (string $direction) => (float) CashFlow::query()
            ->where('station_id', $station->id)
            ->inCurrency($currency)
            ->where('direction', $direction)
            ->sum('amount');
        $manualIn = $manualCashFlows('in');
        $manualOut = $manualCashFlows('out');

        $revenueByDay = $sales()
            ->whereBetween('sold_at', LocalTime::dayRange($today->copy()->subDays(6), $today))
            ->selectRaw(LocalTime::dateSql('sold_at').' as day, SUM(total_amount) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'day' => $row->day,
                'revenue' => $currency->round($row->revenue),
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'today_revenue' => $currency->round($todaySales->clone()->sum('total_amount')),
                'today_volume' => round((float) $todaySales->clone()->sum('volume'), 2),
                'today_transactions' => $todaySales->clone()->count(),
                'net_cash' => $currency->round($cashSales + $manualIn - $deliveries - $manualOut),
            ],
            'tanks' => Tank::query()
                ->where('station_id', $station->id)
                ->with('fuelType:id,name,unit')
                ->orderBy('name')
                ->get()
                ->map(fn (Tank $tank): array => [
                    'id' => $tank->id,
                    'name' => $tank->name,
                    'fuel_type' => $tank->fuelType->name,
                    'unit' => $tank->fuelType->unit,
                    'capacity' => round((float) $tank->capacity, 2),
                    'current_volume' => round((float) $tank->current_volume, 2),
                    'fill_percentage' => $tank->fillPercentage(),
                ]),
            'recentSales' => $sales()
                ->with(['pump:id,name', 'fuelType:id,name,unit'])
                ->latest('sold_at')
                ->limit(8)
                ->get(),
            'revenueByDay' => $revenueByDay,
        ]);
    }
}
