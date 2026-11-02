<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * V1.2: audit log filters + CSV export.
 */
class AuditExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_filters_by_actor_and_action(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_platform_admin' => true]);

        $alice = User::factory()->create(['name' => 'Alice Auditor']);
        AuditEvent::query()->create([
            'action' => 'auth.login',
            'actor_id' => $alice->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
        AuditEvent::query()->create([
            'action' => 'billing.payment_succeeded',
            'actor_id' => $alice->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/activity?actor=Alice&action=auth.login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('events.data', 1)
                ->where('events.data.0.action', 'auth.login'));

        $this->actingAs($admin)
            ->get('/admin/activity?actor=nomatch')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('events.data', 0));
    }

    public function test_activity_exports_to_csv_with_filters(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_platform_admin' => true]);

        AuditEvent::query()->create([
            'action' => 'auth.login',
            'actor_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/activity/export?action=auth.login')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('auth.login', $response->getContent());
    }
}
