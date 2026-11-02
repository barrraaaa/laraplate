<?php

namespace Tests\Feature;

use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_with_the_plan_catalog(): void
    {
        $this->seed(PlanSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->has('plans', 3)
                ->where('canLogin', true)
                ->where('canRegister', true));
    }

    public function test_legal_placeholder_pages_render(): void
    {
        $this->get('/terms')->assertOk()->assertInertia(fn ($page) => $page->component('legal/Terms'));
        $this->get('/privacy')->assertOk()->assertInertia(fn ($page) => $page->component('legal/Privacy'));
    }
}
