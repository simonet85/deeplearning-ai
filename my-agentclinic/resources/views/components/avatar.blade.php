@props(['user' => null, 'name' => null])

@php
    $label = $name ?? ($user ? $user->name : '');
    $photo = $user ? $user->profile_photo_path : null;
@endphp

@if ($photo)
    <img src="{{ route('users.photo', $user) }}?v={{ substr(md5($photo), 0, 8) }}" alt="" {{ $attributes->merge(['class' => 'rounded-full object-cover bg-gray-100']) }}>
@else
    <span aria-hidden="true" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700']) }}>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($label, 0, 1)) }}</span>
@endif
