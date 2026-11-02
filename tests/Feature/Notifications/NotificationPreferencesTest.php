<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD 5.7 P1: per-user notification preferences gate the email channel;
 * in-app notifications always remain.
 */
class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_persist_through_the_profile_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.notification-preferences'), [
                'payment_success' => false,
                'payment_failure' => true,
                'product_news' => false,
            ])
            ->assertRedirect(route('profile.edit'));

        $user = $user->fresh();

        $this->assertFalse($user->wantsEmail('payment_success'));
        $this->assertTrue($user->wantsEmail('payment_failure'));
        $this->assertFalse($user->wantsEmail('product_news'));
    }

    public function test_defaults_are_enabled_when_no_preferences_exist(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->wantsEmail('payment_success'));
        $this->assertTrue($user->wantsEmail('payment_failure'));
        $this->assertTrue($user->wantsEmail('product_news'));
    }
}
