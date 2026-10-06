<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AgentClinic') }}</title>


        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-surface font-sans text-ink antialiased">
        <div class="site-shell">
            <x-layout.header>{{ $header ?? '' }}</x-layout.header>

            <x-layout.main>{{ $slot }}</x-layout.main>

            <x-layout.footer />
        </div>
    </body>
</html>
