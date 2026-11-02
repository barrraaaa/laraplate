<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Tests\TestCase;

/**
 * Google OAuth login/link flow (mock provider; credentials absent from
 * the test env, so the routes 404 until configured).
 */
class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_routes_are_hidden_without_credentials(): void
    {
        $this->get(route('auth.google.redirect'))->assertNotFound();
    }

    public function test_google_callback_creates_and_logs_in_the_user(): void
    {
        config()->set('services.google.client_id', 'test-id');
        config()->set('services.google.client_secret', 'test-secret');

        $socialite = $this->createMock(SocialiteUser::class);
        $socialite->method('getEmail')->willReturn('googler@example.com');
        $socialite->method('getName')->willReturn('Google Person');

        $this->mock('Laravel\Socialite\Contracts\Factory', function ($mock) use ($socialite) {
            $mock->shouldReceive('driver->user')->once()->andReturn($socialite);
        });

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'googler@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->personalOrganization());
    }

    public function test_existing_email_is_linked_not_duplicated(): void
    {
        config()->set('services.google.client_id', 'test-id');
        config()->set('services.google.client_secret', 'test-secret');

        $existing = User::factory()->create([
            'email' => 'linked@example.com',
            'email_verified_at' => null,
        ]);

        $socialite = $this->createMock(SocialiteUser::class);
        $socialite->method('getEmail')->willReturn('linked@example.com');
        $socialite->method('getName')->willReturn('Linked Person');

        $this->mock('Laravel\Socialite\Contracts\Factory', function ($mock) use ($socialite) {
            $mock->shouldReceive('driver->user')->once()->andReturn($socialite);
        });

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame(1, User::query()->where('email', 'linked@example.com')->count());
        $this->assertNotNull($existing->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($existing);
    }
}
