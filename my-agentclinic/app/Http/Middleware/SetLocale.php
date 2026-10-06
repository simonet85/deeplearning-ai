<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sets the language of the request (see App\Support\Locales::resolve) for the text and for dates. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Locales::resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        // Error pages that run outside this middleware (such as maintenance) use this to know it did not run.
        $request->attributes->set('locale_resolved', true);

        return $next($request);
    }
}
