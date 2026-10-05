<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\UserPhotoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Language switcher: remembers the choice in a cookie and, when signed in, on the account.
Route::get('locale/{locale}', LocaleController::class)->name('locale');

// The staff dashboard. Someone who can only use the agent area is sent there; anyone else without the
// permission gets a 403.
Route::get('dashboard', function () {
    $user = auth()->user();

    if ($user->can('dashboard.view')) {
        return view('dashboard');
    }

    abort_unless($user->can('my-appointments.use'), 403);

    return redirect()->route('agent.home');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Staff pages: each needs its own permission (see App\Support\Access).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('ailments', 'ailments')->middleware('can:ailments.view')->name('ailments');
    Route::view('therapies', 'therapies')->middleware('can:therapies.view')->name('therapies');
    Route::view('availability', 'availability')->middleware('can:availability.view')->name('availability');
    Route::view('appointments', 'appointments')->middleware('can:appointments.view')->name('appointments');
});

// Agent area: every page shows only the signed-in agent's own data.
Route::middleware(['auth', 'verified'])->prefix('me')->group(function () {
    Route::redirect('/', '/me/appointments')->middleware('can:my-appointments.use')->name('agent.home');
    Route::view('appointments', 'my-appointments')->middleware('can:my-appointments.use')->name('agent.appointments');
    Route::view('ailments', 'my-ailments')->middleware('can:my-ailments.use')->name('agent.ailments');
});

// Administration: who may do what.
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::view('users', 'users')->middleware('can:users.manage')->name('admin.users');
    Route::view('roles', 'roles')->middleware('can:roles.manage')->name('admin.roles');
});

// Profile photos are served by a route that checks who is asking; see UserPhotoController.
Route::get('users/{user}/photo', UserPhotoController::class)
    ->middleware(['auth'])
    ->name('users.photo');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

// Development only: shows each designed error page so it can be reviewed in a browser (`?retry=90` adds a wait time to
// 429 and 503). Anywhere else this answers like any unknown URL.
Route::get('_errors/{code}', function (Request $request, int $code) {
    abort_unless(app()->environment(['local', 'testing']), 404);

    abort($code, '', $request->filled('retry') ? ['Retry-After' => (int) $request->query('retry')] : []);
})->whereIn('code', [403, 404, 419, 429, 500, 503])->name('errors.gallery');

// Any other URL: running it through the web middleware means the 404 page is shown in the visitor's language.
Route::fallback(fn () => abort(404));
