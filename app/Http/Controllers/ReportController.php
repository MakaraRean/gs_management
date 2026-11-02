<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Currency;
use App\Models\FuelDelivery;
use App\Models\Sale;
use App\Models\Station;
use App\Models\Tank;
use App\Support\LocalTime;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->string('type')->toString() ?: 'daily_sales';
        $from = $request->date('from') ?? LocalTime::today()->subDays(29);
        $to = $request->date('to') ?? LocalTime::today();

        $station = $this->currentStation();
        $currency = $this->currentCurrency();

        $report = match ($type) {
            'inventory' => $this->inventoryReport($station),
            'cash_flow' => $this->cashFlowReport($from, $to, $station, $currency),
            default => $this->dailySalesReport($from, $to, $station, $currency),
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
    private function dailySalesReport(CarbonInterface $from, CarbonInterface $to, Station $station, Currency $currency): array
    {
        $rows = Sale::query()
            ->inStationCurrency($station, $currency)
            ->whereBetween('sold_at', LocalTime::dayRange($from, $to))
            ->selectRaw(LocalTime::dateSql('sold_at').' as date, COUNT(*) as transactions, SUM(volume) as volume, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderByDesc('date')
            ->get()
            ->map(fn ($row): array => [
                'date' => $row->date,
                'transactions' => (int) $row->transactions,
                'volume' => round((float) $row->volume, 2),
                'revenue' => $currency->round($row->revenue),
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
                'revenue' => $currency->round($rows->sum('revenue')),
            ],
            'money_keys' => ['revenue'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventoryReport(Station $station): array
    {
        $rows = Tank::query()
            ->where('station_id', $station->id)
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
            'money_keys' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cashFlowReport(CarbonInterface $from, CarbonInterface $to, Station $station, Currency $currency): array
    {
        $range = LocalTime::dayRange($from, $to);

        $sales = fn (string $paymentMethod) => (float) Sale::query()
            ->inStationCurrency($station, $currency)
            ->where('payment_method', $paymentMethod)
            ->whereBetween('sold_at', $range)
            ->sum('total_amount');
        $manualCashFlows = fn (string $direction) => (float) CashFlow::query()
            ->inStationCurrency($station, $currency)
            ->where('direction', $direction)
            ->whereBetween('occurred_at', $range)
            ->sum('amount');

        $cashSales = $sales('cash');
        $cardSales = $sales('card');
        $deliveries = (float) FuelDelivery::query()
            ->inStationCurrency($station, $currency)
            ->whereBetween('delivered_at', $range)
            ->sum('total_cost');
        $manualIn = $manualCashFlows('in');
        $manualOut = $manualCashFlows('out');

        $rows = collect([
            ['line' => __('Cash fuel sales'), 'direction' => __('In'), 'amount' => $currency->round($cashSales)],
            ['line' => __('Other income (manual)'), 'direction' => __('In'), 'amount' => $currency->round($manualIn)],
            ['line' => __('Fuel purchases'), 'direction' => __('Out'), 'amount' => $currency->round($deliveries)],
            ['line' => __('Expenses (manual)'), 'direction' => __('Out'), 'amount' => $currency->round($manualOut)],
        ]);

        $net = $currency->round($cashSales + $manualIn - $deliveries - $manualOut);

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
                'card_sales' => $currency->round($cardSales),
            ],
            'money_keys' => ['amount', 'net_cash', 'card_sales'],
        ];
    }
}
