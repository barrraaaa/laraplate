<?php

namespace Tests\Feature\Console;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The `laraplate:install` wizard must run cleanly headless (-n): env
 * validation, migrations, seeders and the summary checklist.
 */
class LaraplateInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_installer_runs_non_interactively_and_seeds_the_catalog(): void
    {
        $this->artisan('laraplate:install', ['-n' => true])
            ->expectsOutputToContain('🚀 BOOM!')
            ->expectsOutputToContain('Database migrated!')
            ->assertSuccessful();

        $this->assertSame(
            ['free', 'pro', 'business'],
            Plan::query()->orderBy('sort_order')->pluck('slug')->all(),
        );
    }

    public function test_installer_is_idempotent(): void
    {
        $this->artisan('laraplate:install', ['-n' => true])->assertSuccessful();
        $this->artisan('laraplate:install', ['-n' => true])->assertSuccessful();

        $this->assertSame(3, Plan::query()->count());
    }
}
