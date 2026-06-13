<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\FuelDelivery;
use App\Models\Sale;
use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->business_id) {
            return Inertia::render('dashboard/locked');
        }

        $today = Carbon::today();

        $todaySales = Sale::query()->whereDate('sold_at', $today);

        $cashSales = (float) Sale::query()->where('payment_method', 'cash')->sum('total_amount');
        $deliveries = (float) FuelDelivery::query()->sum('total_cost');
        $manualIn = (float) CashFlow::query()->where('direction', 'in')->sum('amount');
        $manualOut = (float) CashFlow::query()->where('direction', 'out')->sum('amount');

        $revenueByDay = Sale::query()
            ->where('sold_at', '>=', $today->copy()->subDays(6)->startOfDay())
            ->selectRaw('DATE(sold_at) as day, SUM(total_amount) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'day' => $row->day,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'today_revenue' => round((float) $todaySales->clone()->sum('total_amount'), 2),
                'today_volume' => round((float) $todaySales->clone()->sum('volume'), 2),
                'today_transactions' => $todaySales->clone()->count(),
                'net_cash' => round($cashSales + $manualIn - $deliveries - $manualOut, 2),
            ],
            'tanks' => Tank::query()
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
            'recentSales' => Sale::query()
                ->with(['pump:id,name', 'fuelType:id,name,unit'])
                ->latest('sold_at')
                ->limit(8)
                ->get(),
            'revenueByDay' => $revenueByDay,
        ]);
    }
}
