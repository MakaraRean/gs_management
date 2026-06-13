<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashFlowRequest;
use App\Models\CashFlow;
use App\Models\FuelDelivery;
use App\Models\Sale;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CashFlowController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->date('from') ?? Carbon::today()->subDays(29);
        $to = $request->date('to') ?? Carbon::today();
        $station = $this->currentStation();

        $entries = $this->ledger($from, $to, $station);

        $totalIn = $entries->where('direction', 'in')->sum('amount');
        $totalOut = $entries->where('direction', 'out')->sum('amount');

        return Inertia::render('cash-flow/index', [
            'entries' => $entries->values(),
            'summary' => [
                'total_in' => round($totalIn, 2),
                'total_out' => round($totalOut, 2),
                'net' => round($totalIn - $totalOut, 2),
            ],
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    public function store(CashFlowRequest $request): RedirectResponse
    {
        CashFlow::create([
            ...$request->validated(),
            'user_id' => $request->user()?->id,
            'station_id' => $this->currentStation()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cash entry recorded.')]);

        return to_route('cash-flow.index');
    }

    public function destroy(CashFlow $cashFlow): RedirectResponse
    {
        abort_unless($cashFlow->station_id === $this->currentStation()->id, 403);

        $cashFlow->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cash entry deleted.')]);

        return to_route('cash-flow.index');
    }

    /**
     * Build a unified cash ledger: daily cash sales (in), deliveries (out), manual entries.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function ledger(Carbon $from, Carbon $to, Station $station): Collection
    {
        $cashSales = Sale::query()
            ->where('station_id', $station->id)
            ->where('payment_method', 'cash')
            ->whereBetween('sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('DATE(sold_at) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->get()
            ->map(fn ($row): array => [
                'id' => 'sale-'.$row->day,
                'date' => $row->day,
                'direction' => 'in',
                'category' => 'sale',
                'description' => __('Cash fuel sales'),
                'amount' => round((float) $row->total, 2),
                'is_manual' => false,
            ]);

        $deliveries = FuelDelivery::query()
            ->whereHas('tank', fn ($q) => $q->where('station_id', $station->id))
            ->with('tank:id,name')
            ->whereBetween('delivered_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get()
            ->map(fn (FuelDelivery $delivery): array => [
                'id' => 'delivery-'.$delivery->id,
                'date' => $delivery->delivered_at->toDateString(),
                'direction' => 'out',
                'category' => 'fuel_purchase',
                'description' => trim(__('Fuel delivery to :tank', ['tank' => $delivery->tank->name]).($delivery->supplier ? ' — '.$delivery->supplier : '')),
                'amount' => round((float) $delivery->total_cost, 2),
                'is_manual' => false,
            ]);

        $manual = CashFlow::query()
            ->where('station_id', $station->id)
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get()
            ->map(fn (CashFlow $entry): array => [
                'id' => $entry->id,
                'date' => $entry->occurred_at->toDateString(),
                'direction' => $entry->direction,
                'category' => $entry->category,
                'description' => $entry->description,
                'amount' => round((float) $entry->amount, 2),
                'is_manual' => true,
            ]);

        return $cashSales
            ->concat($deliveries)
            ->concat($manual)
            ->sortByDesc('date');
    }
}
