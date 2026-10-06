<?php

namespace App\Support;

use Illuminate\Http\Request;

/** The languages the app is available in, and how to pick one for a request. */
final class Locales
{
    /** @return list<string> */
    public static function supported(): array
    {
        return config('app.supported_locales');
    }

    /** The language used when nothing else applies. */
    public static function default(): string
    {
        return config('app.fallback_locale');
    }

    public static function isSupported(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, self::supported(), true);
    }

    /** The supported language the browser prefers, taking regions and quality values into account. */
    public static function fromBrowser(Request $request): string
    {
        $preferred = $request->getPreferredLanguage(self::supported());

        return self::isSupported($preferred) ? $preferred : self::default();
    }

    /**
     * The language for a request, in order: the signed-in user's saved choice, the `locale` cookie, the browser's
     * `Accept-Language`, then the default.
     */
    public static function resolve(Request $request): string
    {
        foreach ([$request->user()?->locale, $request->cookie('locale')] as $candidate) {
            if (self::isSupported($candidate)) {
                return $candidate;
            }
        }

        return self::fromBrowser($request);
    }
}
