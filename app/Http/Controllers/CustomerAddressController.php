<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CustomerAddressController extends Controller
{
    public function store(AddressRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $data = $request->validated();

        if (! empty($data['address_id'])) {
            $address = Address::findOrFail($data['address_id']);
        } else {
            $address = Address::create([
                'street' => $data['street'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'country' => $data['country'] ?? 'KH',
            ]);
        }

        $customer->update(['address_id' => $address->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('addresses.created')]);

        return to_route('customers.show', $customer);
    }

    public function update(AddressRequest $request, Customer $customer, Address $address): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $data = $request->validated();
        $address->update([
            'street' => $data['street'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'country' => $data['country'] ?? 'KH',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('addresses.updated')]);

        return to_route('customers.show', $customer);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $customer->update(['address_id' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('addresses.unlinked')]);

        return to_route('customers.show', $customer);
    }

    private function authorizeCustomer(Customer $customer): void
    {
        abort_unless($customer->business_id === $this->currentBusiness()->id, 403);
    }
}
