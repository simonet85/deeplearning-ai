<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authorize;
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
        // Without this, the `can:` permission check on a page would not apply to its components' actions.
        Livewire::addPersistentMiddleware([Authorize::class]);
    }
}
