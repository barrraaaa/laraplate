<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admins_are_rejected_from_the_admin_panel(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_platform_admin_can_open_the_dashboard(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Dashboard')
                ->has('metrics.totalUsers')
                ->has('signups'));
    }

    public function test_platform_admin_can_open_system_health(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/health')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/SystemHealth'));
    }

    public function test_platform_admin_can_browse_users(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_platform_admin' => true,
        ]);

        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/Users'));
    }
}
