<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class HandleLocale
{
    private const SUPPORTED = ['km', 'en'];

    private const DEFAULT = 'km';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale', self::DEFAULT);

        App::setLocale(in_array($locale, self::SUPPORTED) ? $locale : self::DEFAULT);

        return $next($request);
    }
}
