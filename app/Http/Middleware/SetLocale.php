<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Picks the language for this request (App\Support\Locale::detect) for the shop and the admin. */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = Locale::detect($request);
        app()->setLocale($locale);
        Carbon::setLocale($locale); // "2 hours ago" → "২ ঘন্টা আগে"

        return $next($request);
    }
}
