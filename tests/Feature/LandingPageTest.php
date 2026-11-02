<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_the_sales_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->has('store')
                ->where('canLogin', true)
                ->where('canRegister', true));
    }

    public function test_deals_page_renders(): void
    {
        $this->get('/deals')->assertOk()->assertInertia(fn ($page) => $page->component('Deals'));
    }

    public function test_legal_placeholder_pages_render(): void
    {
        $this->get('/terms')->assertOk()->assertInertia(fn ($page) => $page->component('legal/Terms'));
        $this->get('/privacy')->assertOk()->assertInertia(fn ($page) => $page->component('legal/Privacy'));
    }
}
