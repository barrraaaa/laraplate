<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaraplateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_command_exits_successfully_on_a_healthy_installation(): void
    {
        $this->artisan('laraplate:status')
            ->expectsOutputToContain('Laraplate installation status')
            ->expectsOutputToContain('Database connection')
            ->assertSuccessful();
    }
}
