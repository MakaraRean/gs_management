<?php

namespace App\Http\Middleware;

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
            Inertia::flash('toast', ['type' => 'info', 'message' => __('onboarding.station_required')]);

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
