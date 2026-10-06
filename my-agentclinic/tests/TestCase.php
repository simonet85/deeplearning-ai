<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Roles and permissions are cached in memory; start every test from a clean slate.
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // The language middleware sets Carbon's global locale; do not let one test's language leak into the next.
        Carbon::setLocale('en');
    }
}
