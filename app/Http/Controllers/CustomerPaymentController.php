<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPaymentRequest;
use App\Http\Requests\RequestDeletionRequest;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerPaymentController extends Controller
{
    public function store(CustomerPaymentRequest $request, Customer $customer): RedirectResponse
    {
        abort_unless($customer->business_id === $this->currentBusiness()->id, 403);

        $data = $request->validated();
        $paidAt = $data['paid_at'] ?? now();
        $stationId = $this->currentStation()->id;

        $customer->payments()->create([
            'user_id' => $request->user()?->id,
            'station_id' => $stationId,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null,
            'paid_at' => $paidAt,
        ]);

        CashFlow::create([
            'user_id' => $request->user()?->id,
            'station_id' => $stationId,
            'direction' => 'in',
            'category' => 'credit_payment',
            'amount' => $data['amount'],
            'description' => __('credit.payment_description', ['customer' => $customer->name]),
            'occurred_at' => $paidAt,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('credit.payment_recorded')]);

        return to_route('customers.show', $customer);
    }

    public function destroy(Request $request, Customer $customer, CustomerPayment $payment): RedirectResponse
    {
        abort_unless($customer->business_id === $this->currentBusiness()->id, 403);
        abort_unless($payment->customer_id === $customer->id, 404);

        $payment->deleteApprovedBy($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('credit.payment_deleted')]);

        return to_route('customers.show', $customer);
    }

    public function requestDeletion(RequestDeletionRequest $request, Customer $customer, CustomerPayment $payment): RedirectResponse
    {
        $business = $this->currentBusiness();

        abort_unless($customer->business_id === $business->id, 403);
        abort_unless($payment->customer_id === $customer->id, 404);

        $payment->requestDeletion($request->user(), $request->validated('reason'), $business, $this->currentStation());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('deletion_requests.sent')]);

        return back();
    }
}
