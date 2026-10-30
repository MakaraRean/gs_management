<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasStation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->business?->stations()->exists()) {
            $message = $request->user()?->hasPermission(Permission::Stations)
                ? __('onboarding.station_required')
                : __('onboarding.station_required_staff');

            Inertia::flash('toast', ['type' => 'info', 'message' => $message]);

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
