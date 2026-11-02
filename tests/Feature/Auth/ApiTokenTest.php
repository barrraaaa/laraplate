<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Welcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * PRD 5.8: personal API tokens (Sanctum) with ability scoping, plus
 * throttled authentication endpoints (PRD 5.13).
 */
class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_token_and_use_it(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('api-tokens.store'), [
                'name' => 'CI deploy',
                'abilities' => ['read'],
            ])
            ->assertRedirect()
            ->assertSessionHas('plainTextToken');

        $token = session('plainTextToken');
        $this->assertIsString($token);

        // Sanctum is stateful in tests — drop the session AND the acting-as
        // user so the request authenticates purely through the bearer token.
        $this->flushSession();
        Auth::guard('web')->forgetUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('abilities', ['read']);
    }

    public function test_unauthenticated_api_requests_are_rejected(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_can_revoke_a_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('temp', ['read']);

        $this->actingAs($user)
            ->delete(route('api-tokens.destroy', $token->accessToken))
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);

        $this->flushSession();
        Auth::guard('web')->forgetUser();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_a_user_cannot_revoke_another_users_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $intruder = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('victim', ['read']);

        $this->actingAs($intruder)
            ->delete(route('api-tokens.destroy', $token->accessToken))
            ->assertNotFound();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_registration_sends_the_queued_welcome_notification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Welcome User',
            'email' => 'welcome@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'welcome@example.com')->firstOrFail();

        Notification::assertSentTo($user, Welcome::class);
    }
}
