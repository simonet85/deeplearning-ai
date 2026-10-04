<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Roles and permissions are cached in memory; start every test from a clean slate.
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
