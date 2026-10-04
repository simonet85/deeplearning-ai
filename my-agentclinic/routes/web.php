<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Agents have their own area; the staff dashboard sends them there.
Route::get('dashboard', fn () => auth()->user()->isAgent() ? redirect()->route('agent.home') : view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Staff pages: administrators and therapists only.
Route::middleware(['auth', 'verified', 'role:admin,therapist'])->group(function () {
    Route::view('ailments', 'ailments')->name('ailments');
    Route::view('therapies', 'therapies')->name('therapies');
    Route::view('availability', 'availability')->name('availability');
    Route::view('appointments', 'appointments')->name('appointments');
});

// Agent area. Its pages arrive in later slices; until then agents land on their profile.
Route::redirect('me', '/profile')
    ->middleware(['auth', 'verified', 'role:agent'])
    ->name('agent.home');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
