<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Currency;
use App\Models\Station;

abstract class Controller
{
    protected function currentStation(): Station
    {
        return app()->bound('current.station')
            ? app('current.station')
            : abort(500, 'No active station');
    }

    /**
     * The currency new records of the active station are recorded in.
     */
    protected function currentCurrency(): Currency
    {
        return $this->currentStation()->effective_currency;
    }

    protected function currentBusiness(): Business
    {
        return auth()->user()?->business ?? abort(403);
    }
}
