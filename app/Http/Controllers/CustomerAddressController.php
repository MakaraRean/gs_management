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
        $data = $request->validated();

        $label = $data['label'] ?? 'home';
        $isDefault = (bool) ($data['is_default'] ?? false);

        $addressData = array_intersect_key($data, array_flip(['street', 'city', 'province', 'country']));
        $address = Address::create(array_merge(['country' => 'KH'], $addressData));

        if ($isDefault) {
            $customer->addresses()->newPivotQuery()
                ->where('customer_id', $customer->id)
                ->update(['is_default' => false]);
        }

        $customer->addresses()->attach($address->id, [
            'label' => $label,
            'is_default' => $isDefault,
            'is_active' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('addresses.created')]);

        return to_route('customers.show', $customer);
    }

    public function update(AddressRequest $request, Customer $customer, Address $address): RedirectResponse
    {
        $data = $request->validated();
        $isDefault = (bool) ($data['is_default'] ?? false);

        $addressData = array_intersect_key($data, array_flip(['street', 'city', 'province', 'country']));
        $address->update($addressData);

        if ($isDefault) {
            $customer->addresses()->newPivotQuery()
                ->where('customer_id', $customer->id)
                ->update(['is_default' => false]);
        }

        $customer->addresses()->updateExistingPivot($address->id, [
            'label' => $data['label'] ?? 'home',
            'is_default' => $isDefault,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('addresses.updated')]);

        return to_route('customers.show', $customer);
    }

    public function destroy(Customer $customer, Address $address): RedirectResponse
    {
        $customer->addresses()->updateExistingPivot($address->id, ['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('addresses.unlinked')]);

        return to_route('customers.show', $customer);
    }
}
