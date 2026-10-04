<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('ailments', 'ailments')
    ->middleware(['auth', 'verified'])
    ->name('ailments');

Route::view('therapies', 'therapies')
    ->middleware(['auth', 'verified'])
    ->name('therapies');

Route::view('availability', 'availability')
    ->middleware(['auth', 'verified'])
    ->name('availability');

Route::view('appointments', 'appointments')
    ->middleware(['auth', 'verified'])
    ->name('appointments');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
