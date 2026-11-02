<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // spatie caches roles/permissions in memory across tests; forget
        // them so each test starts against a clean registry.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
