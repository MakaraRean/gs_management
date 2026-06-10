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

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->string('type')->toString() ?: 'daily_sales';
        $from = $request->date('from') ?? Carbon::today()->subDays(29);
        $to = $request->date('to') ?? Carbon::today();

        $report = match ($type) {
            'inventory' => $this->inventoryReport(),
            'cash_flow' => $this->cashFlowReport($from, $to),
            default => $this->dailySalesReport($from, $to),
        };

        return Inertia::render('reports/index', [
            'type' => $type,
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'report' => $report,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dailySalesReport(Carbon $from, Carbon $to): array
    {
        $rows = Sale::query()
            ->whereBetween('sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as transactions, SUM(volume) as volume, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderByDesc('date')
            ->get()
            ->map(fn ($row): array => [
                'date' => $row->date,
                'transactions' => (int) $row->transactions,
                'volume' => round((float) $row->volume, 2),
                'revenue' => round((float) $row->revenue, 2),
            ]);

        return [
            'title' => __('Daily Sales Report'),
            'columns' => [
                ['key' => 'date', 'label' => __('Date')],
                ['key' => 'transactions', 'label' => __('Transactions')],
                ['key' => 'volume', 'label' => __('Volume (L)')],
                ['key' => 'revenue', 'label' => __('Revenue')],
            ],
            'rows' => $rows,
            'totals' => [
                'transactions' => (int) $rows->sum('transactions'),
                'volume' => round($rows->sum('volume'), 2),
                'revenue' => round($rows->sum('revenue'), 2),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventoryReport(): array
    {
        $rows = Tank::query()
            ->with('fuelType')
            ->orderBy('name')
            ->get()
            ->map(fn (Tank $tank): array => [
                'tank' => $tank->name,
                'fuel_type' => $tank->fuelType->name,
                'capacity' => round((float) $tank->capacity, 2),
                'current_volume' => round((float) $tank->current_volume, 2),
                'fill' => $tank->fillPercentage().'%',
            ]);

        return [
            'title' => __('Fuel Inventory Report'),
            'columns' => [
                ['key' => 'tank', 'label' => __('Tank')],
                ['key' => 'fuel_type', 'label' => __('Fuel Type')],
                ['key' => 'capacity', 'label' => __('Capacity (L)')],
                ['key' => 'current_volume', 'label' => __('Current (L)')],
                ['key' => 'fill', 'label' => __('Fill')],
            ],
            'rows' => $rows,
            'totals' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cashFlowReport(Carbon $from, Carbon $to): array
    {
        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $cashSales = (float) Sale::query()->where('payment_method', 'cash')->whereBetween('sold_at', $range)->sum('total_amount');
        $cardSales = (float) Sale::query()->where('payment_method', 'card')->whereBetween('sold_at', $range)->sum('total_amount');
        $deliveries = (float) FuelDelivery::query()->whereBetween('delivered_at', $range)->sum('total_cost');
        $manualIn = (float) CashFlow::query()->where('direction', 'in')->whereBetween('occurred_at', $range)->sum('amount');
        $manualOut = (float) CashFlow::query()->where('direction', 'out')->whereBetween('occurred_at', $range)->sum('amount');

        $rows = collect([
            ['line' => __('Cash fuel sales'), 'direction' => __('In'), 'amount' => round($cashSales, 2)],
            ['line' => __('Other income (manual)'), 'direction' => __('In'), 'amount' => round($manualIn, 2)],
            ['line' => __('Fuel purchases'), 'direction' => __('Out'), 'amount' => round($deliveries, 2)],
            ['line' => __('Expenses (manual)'), 'direction' => __('Out'), 'amount' => round($manualOut, 2)],
        ]);

        $net = round($cashSales + $manualIn - $deliveries - $manualOut, 2);

        return [
            'title' => __('Cash Flow Statement'),
            'columns' => [
                ['key' => 'line', 'label' => __('Line item')],
                ['key' => 'direction', 'label' => __('Direction')],
                ['key' => 'amount', 'label' => __('Amount')],
            ],
            'rows' => $rows,
            'totals' => [
                'net_cash' => $net,
                'card_sales' => round($cardSales, 2),
            ],
        ];
    }
}
