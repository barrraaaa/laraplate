<?php

namespace Tests\Feature\Auth;

use App\Mail\Auth\MagicLoginMail;
use App\Models\User;
use App\Modules\Auth\Models\MagicLogin;
use App\Modules\Auth\Services\MagicLinkService;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ShipFast-style passwordless auth (magic links).
 */
class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_queues_a_login_link_without_revealing_account_state(): void
    {
        Mail::fake();

        $this->post(route('auth.magic.send'), ['email' => 'newcomer@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'magic-link-sent');

        $this->assertDatabaseHas('magic_logins', ['email' => 'newcomer@example.com']);
    }

    public function test_consuming_a_link_creates_a_verified_user_and_logs_in(): void
    {
        Mail::fake();
        app(MagicLinkService::class)->send('magic@example.com');

        $magic = MagicLogin::query()->firstOrFail();
        Mail::assertQueued(MagicLoginMail::class, fn ($mail) => true);

        // Extract the plain token from the queued mail via reflection-free
        // path: send() hashes — rebuild with a known token instead.
        $knownToken = Str::random(64);
        MagicLogin::query()->create([
            'email' => 'person@example.com',
            'token_hash' => hash('sha256', $knownToken),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->get('/auth/magic/'.$knownToken)
            ->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'person@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull(app(OrganizationService::class)->personalOrganizationFor($user));
        $this->assertSame(1, MagicLogin::query()->where('user_id', $user->id)->count());
    }

    public function test_expired_links_are_rejected(): void
    {
        $knownToken = Str::random(64);
        MagicLogin::query()->create([
            'email' => 'expired@example.com',
            'token_hash' => hash('sha256', $knownToken),
            'expires_at' => now()->subMinute(),
        ]);

        $this->get('/auth/magic/'.$knownToken)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('magic');
    }

    public function test_tokens_are_single_use(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $knownToken = Str::random(64);
        $magic = MagicLogin::create([
            'email' => $user->email,
            'token_hash' => hash('sha256', $knownToken),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->get('/auth/magic/'.$knownToken)->assertRedirect(route('dashboard'));

        $this->post(route('logout'));
        $this->get('/auth/magic/'.$knownToken)->assertRedirect(route('login'));
    }

    public function test_disabling_the_feature_turns_the_route_off(): void
    {
        config()->set('saas.features.magic_links', false);

        $this->get('/auth/magic/whatever')->assertNotFound();
    }
}
