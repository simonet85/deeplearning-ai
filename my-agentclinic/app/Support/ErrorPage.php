<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** What the error pages need to know about the visitor and the request. */
final class ErrorPage
{
    /**
     * Pages that run inside the web middleware are already in the visitor's language. Some errors happen before it
     * (maintenance mode, for one), when there is no session or cookie to read, so use the browser's language.
     */
    public static function ensureLocale(Request $request): void
    {
        if ($request->attributes->get('locale_resolved')) {
            return;
        }

        app()->setLocale(Locales::fromBrowser($request));
    }

    /**
     * Where the main "way out" button goes, by who is looking: staff to the dashboard, an agent to their
     * appointments, anyone else signed in to their profile, and a visitor to the welcome page.
     *
     * @return array{label: string, url: string}
     */
    public static function wayOut(?User $user): array
    {
        return match (true) {
            $user?->can('dashboard.view') => ['label' => __('Dashboard'), 'url' => route('dashboard')],
            $user?->can('my-appointments.use') => ['label' => __('My appointments'), 'url' => route('agent.home')],
            $user !== null => ['label' => __('Profile'), 'url' => route('profile')],
            default => ['label' => __('Back to the home page'), 'url' => url('/')],
        };
    }

    /** The number of seconds the response asks to wait (`Retry-After`), when it says so. */
    public static function retryAfter(?Throwable $exception): ?int
    {
        if (! $exception instanceof HttpExceptionInterface) {
            return null;
        }

        $seconds = $exception->getHeaders()['Retry-After'] ?? null;

        return is_numeric($seconds) && $seconds > 0 ? (int) $seconds : null;
    }
}
