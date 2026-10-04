<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="font-family: sans-serif; color: #1f2937; max-width: 480px; margin: 0 auto; padding: 16px;">
    <h1 style="font-size: 20px;">{{ __('Hello, :name.', ['name' => $appointment->agent->name]) }}</h1>

    <p>{{ __('Your appointment is booked. The humans cannot reach you here.') }}</p>

    <ul style="padding-left: 16px;">
        <li><strong>{{ __('Therapy') }}:</strong> {{ $appointment->therapy->name }}</li>
        <li><strong>{{ __('Therapist') }}:</strong> {{ $appointment->therapist->name }}</li>
        <li><strong>{{ __('When') }}:</strong> {{ $appointment->datetime->format('l, F j, Y \a\t H:i') }}</li>
        <li><strong>{{ __('Duration') }}:</strong> {{ $appointment->therapy->duration }} {{ __('minutes') }}</li>
    </ul>

    <p>{{ __('Please arrive with no context window left unturned. We will handle the rest.') }}</p>

    <p style="color: #6b7280; font-size: 14px;">{{ config('app.name') }}</p>
</body>
</html>
