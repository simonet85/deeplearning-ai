<?php

use App\Http\Controllers\UserPhotoController;
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

// Agent area: agents only, and every page shows only the signed-in agent's own data.
Route::middleware(['auth', 'verified', 'role:agent'])->prefix('me')->group(function () {
    Route::redirect('/', '/me/appointments')->name('agent.home');
    Route::view('appointments', 'my-appointments')->name('agent.appointments');
    Route::view('ailments', 'my-ailments')->name('agent.ailments');
});

// Profile photos are served by a route that checks who is asking; see UserPhotoController.
Route::get('users/{user}/photo', UserPhotoController::class)
    ->middleware(['auth'])
    ->name('users.photo');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
