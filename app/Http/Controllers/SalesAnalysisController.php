<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SalesAnalysisController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->date('from') ?? Carbon::today()->subDays(29);
        $to = $request->date('to') ?? Carbon::today();

        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $base = fn () => Sale::query()->whereBetween('sold_at', $range);

        $revenueByDay = $base()
            ->selectRaw('DATE(sold_at) as day, SUM(total_amount) as revenue, SUM(volume) as volume, COUNT(*) as transactions')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'day' => $row->day,
                'revenue' => round((float) $row->revenue, 2),
                'volume' => round((float) $row->volume, 2),
                'transactions' => (int) $row->transactions,
            ]);

        $byFuelType = $base()
            ->join('fuel_types', 'fuel_types.id', '=', 'sales.fuel_type_id')
            ->selectRaw('fuel_types.name, fuel_types.color, SUM(sales.total_amount) as revenue, SUM(sales.volume) as volume')
            ->groupBy('fuel_types.id', 'fuel_types.name', 'fuel_types.color')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'color' => $row->color,
                'revenue' => round((float) $row->revenue, 2),
                'volume' => round((float) $row->volume, 2),
            ]);

        $byPump = $base()
            ->join('pumps', 'pumps.id', '=', 'sales.pump_id')
            ->selectRaw('pumps.name, SUM(sales.total_amount) as revenue')
            ->groupBy('pumps.id', 'pumps.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        $byPayment = $base()
            ->selectRaw('payment_method, SUM(total_amount) as revenue')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->payment_method,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        $totals = $base()
            ->selectRaw('SUM(total_amount) as revenue, SUM(volume) as volume, COUNT(*) as transactions')
            ->first();

        $revenue = round((float) ($totals->revenue ?? 0), 2);
        $transactions = (int) ($totals->transactions ?? 0);

        return Inertia::render('analytics/index', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'revenueByDay' => $revenueByDay,
            'byFuelType' => $byFuelType,
            'byPump' => $byPump,
            'byPayment' => $byPayment,
            'totals' => [
                'revenue' => $revenue,
                'volume' => round((float) ($totals->volume ?? 0), 2),
                'transactions' => $transactions,
                'average_ticket' => $transactions > 0 ? round($revenue / $transactions, 2) : 0,
            ],
        ]);
    }
}
