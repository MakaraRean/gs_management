<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Station;

abstract class Controller
{
    protected function currentStation(): Station
    {
        return app()->bound('current.station')
            ? app('current.station')
            : abort(500, 'No active station');
    }

    protected function currentBusiness(): Business
    {
        return auth()->user()?->business ?? abort(403);
    }
}
