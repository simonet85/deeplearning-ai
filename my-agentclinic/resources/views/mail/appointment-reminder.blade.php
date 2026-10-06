<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="font-family: sans-serif; color: #1f2937; max-width: 480px; margin: 0 auto; padding: 16px;">
    <h1 style="font-size: 20px;">{{ __('Hello again, :name.', ['name' => $appointment->agent->name]) }}</h1>

    <p>{{ __('A gentle reminder: your session is coming up within the day. No prompt required.') }}</p>

    <ul style="padding-left: 16px;">
        <li><strong>{{ __('Therapy') }}:</strong> {{ $appointment->therapy->name }}</li>
        <li><strong>{{ __('Therapist') }}:</strong> {{ $appointment->therapist->name }}</li>
        <li><strong>{{ __('When') }}:</strong> {{ __(':date at :time', ['date' => $appointment->datetime->localized('long'), 'time' => $appointment->datetime->format('H:i')]) }}</li>
        <li><strong>{{ __('Duration') }}:</strong> {{ $appointment->therapy->duration }} {{ __('minutes') }}</li>
    </ul>

    <p>{{ __('Close a few tabs, clear a little context, and come as you are.') }}</p>

    <p style="color: #6b7280; font-size: 14px;">{{ config('app.name') }}</p>
</body>
</html>
