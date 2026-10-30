<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BusinessController extends Controller
{
    public function update(BusinessRequest $request): RedirectResponse
    {
        $business = $this->currentBusiness();

        $business->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('businesses.updated')]);

        return to_route('team.index');
    }
}
