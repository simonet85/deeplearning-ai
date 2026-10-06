<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** Remember the chosen language in a cookie, and on the account when signed in, then go back where we were. */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locales::isSupported($locale), 404);

        $request->user()?->update(['locale' => $locale]);

        return redirect()
            ->back(fallback: '/')
            ->withCookie(cookie('locale', $locale, 60 * 24 * 365));
    }
}
