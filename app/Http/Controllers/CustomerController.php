<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = $this->customerQuery()
            ->orderBy('name')
            ->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'credit_limit' => $c->credit_limit,
                'outstanding_balance' => $c->outstandingBalance(),
            ]);

        return Inertia::render('customers/index', [
            'customers' => $customers,
            'customersPerStation' => $this->currentBusiness()->customers_per_station,
        ]);
    }

    public function show(Customer $customer): Response
    {
        abort_unless($customer->business_id === $this->currentBusiness()->id, 403);

        $customer->load([
            'addresses',
        ]);

        $creditSales = $customer->sales()
            ->where('payment_method', 'credit')
            ->with(['pump', 'fuelType'])
            ->orderByDesc('sold_at')
            ->paginate(20, pageName: 'sales_page');

        $payments = $customer->payments()
            ->with('user')
            ->orderByDesc('paid_at')
            ->paginate(20, pageName: 'payments_page');

        return Inertia::render('customers/show', [
            'customer' => array_merge($customer->toArray(), [
                'outstanding_balance' => $customer->outstandingBalance(),
            ]),
            'creditSales' => $creditSales,
            'payments' => $payments,
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $business = $this->currentBusiness();
        $validated = $request->validated();

        if (isset($validated['station_id'])) {
            abort_unless(
                $business->stations()->where('id', $validated['station_id'])->exists(),
                403
            );
        }

        Customer::create([
            ...$validated,
            'business_id' => $business->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('customers.created')]);

        return to_route('customers.index');
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $business = $this->currentBusiness();
        abort_unless($customer->business_id === $business->id, 403);

        $validated = $request->validated();

        if (isset($validated['station_id'])) {
            abort_unless(
                $business->stations()->where('id', $validated['station_id'])->exists(),
                403
            );
        }

        $customer->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('customers.updated')]);

        return to_route('customers.show', $customer);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        abort_unless($customer->business_id === $this->currentBusiness()->id, 403);

        $customer->deactivate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('customers.deleted')]);

        return to_route('customers.index');
    }

    private function customerQuery(): Builder
    {
        $station = $this->currentStation();
        $business = $this->currentBusiness();

        return Customer::query()
            ->where('business_id', $business->id)
            ->where(fn ($q) => $q->whereNull('station_id')->orWhere('station_id', $station->id));
    }
}
