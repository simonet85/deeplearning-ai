<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Livewire's own update requests do not run a page route's middleware unless it is listed here.
        // Without this, the role check on the staff pages would not apply to their components' actions.
        Livewire::addPersistentMiddleware([EnsureUserHasRole::class]);
    }
}
