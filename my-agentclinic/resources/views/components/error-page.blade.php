{{--
    A complete error page (HTML document): the clinic cross, a big code, a title, a warm message and a way out.
    Used by every view in resources/views/errors. It does not depend on the session or the layout, so it also works
    when the error happens early (maintenance mode, expired session).
--}}
@props([
    'code',
    'title',
    'message',
    'hint' => null,
    'reload' => false,
    'login' => false,
    'home' => true,
    'language' => true,
])

@php
    \App\Support\ErrorPage::ensureLocale(request());

    $wayOut = $home ? \App\Support\ErrorPage::wayOut(auth()->user()) : null;

    // The buttons, in order: the first one is the main action.
    $actions = [];

    if ($reload) {
        $actions[] = ['label' => $reload === true ? __('Reload the page') : $reload, 'href' => url()->current(), 'reload' => true];
    }

    if ($login && ! auth()->check()) {
        $actions[] = ['label' => __('Log in again'), 'href' => url('/login'), 'reload' => false];
    } elseif ($wayOut) {
        $actions[] = ['label' => $wayOut['label'], 'href' => $wayOut['url'], 'reload' => false];
    }

    if ($home && ! $reload) {
        $actions[] = ['label' => __('Go back'), 'href' => 'javascript:history.back()', 'reload' => false];
    }

    $button = 'touch-target inline-flex w-full items-center justify-center rounded-md px-5 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto';
    $primary = $button.' bg-indigo-600 text-white shadow-sm hover:bg-indigo-500';
    $secondary = $button.' border border-gray-300 bg-white text-gray-700 hover:bg-gray-50';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $code }} · {{ $title }} · {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="antialiased font-sans bg-gradient-to-b from-indigo-50 to-white text-gray-800">
        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <div class="w-full max-w-lg text-center">
                <a href="{{ url('/') }}" class="touch-target inline-flex items-center justify-center" aria-label="{{ config('app.name') }}">
                    <x-application-logo class="mx-auto h-16 w-16 fill-current text-indigo-600" />
                </a>

                <p class="mt-6 text-6xl font-semibold tracking-tight text-indigo-600 sm:text-7xl">
                    <span class="sr-only">{{ __('Error') }}</span>{{ $code }}
                </p>
                <h1 class="mt-3 text-2xl font-semibold text-gray-900 sm:text-3xl">{{ $title }}</h1>
                <p class="mt-3 text-gray-600">{{ $message }}</p>
                @if ($hint)
                    <p class="mt-2 text-sm text-gray-500">{{ $hint }}</p>
                @endif

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    @foreach ($actions as $action)
                        <a href="{{ $action['href'] }}" @if ($action['reload']) onclick="location.reload(); return false;" @endif class="{{ $loop->first ? $primary : $secondary }}">{{ $action['label'] }}</a>
                    @endforeach
                </div>

                <div class="mt-10 flex items-center justify-center gap-3 text-sm text-gray-500">
                    <span>{{ config('app.name') }}</span>
                    @if ($language)
                        <x-locale-switcher />
                    @endif
                </div>
            </div>
        </main>
    </body>
</html>
