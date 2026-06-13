<?php

namespace App\Http\Middleware;

use App\Models\Station;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleActiveStation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->business_id) {
            return $next($request);
        }

        $ownStations = Station::query()
            ->with('business')
            ->where('business_id', $user->business_id);

        $stationId = $request->cookie('station_id');

        $station = $stationId
            ? $ownStations->clone()->find($stationId)
            : null;

        if (! $station || ! $station->is_active) {
            $station = $ownStations->clone()->first();
        }

        if ($station) {
            app()->instance('current.station', $station);
        }

        return $next($request);
    }
}
