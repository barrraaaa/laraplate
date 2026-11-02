<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Auth\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OTPHP\TOTP;
use Tests\TestCase;

/**
 * PRD 5.1 P1: TOTP two-factor with recovery codes, challenge gating and
 * enrollment flow.
 */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function codeFor(User $user): string
    {
        $totp = TOTP::createFromSecret($user->two_factor_secret);
        $totp->setIssuer((string) config('app.name'));
        $totp->setLabel($user->email);

        return $totp->now();
    }

    public function test_enrollment_to_enforced_challenge_flow(): void
    {
        $user = User::factory()->create();

        $service = app(TwoFactorService::class);

        // Begin enrollment.
        $this->actingAs($user)
            ->post(route('two-factor.enable'))
            ->assertRedirect();

        $user = $user->fresh();
        $this->assertTrue($service->isPendingConfirmation($user));

        // Wrong code fails.
        try {
            app(TwoFactorService::class)->confirmEnrollment($user, '000000');
            $this->fail('Expected invalid code to throw');
        } catch (\RuntimeException) {
            $this->addToAssertionCount(1);
        }

        // Correct code confirms + yields 10 recovery codes.
        $codes = app(TwoFactorService::class)->confirmEnrollment($user, $this->codeFor($user));

        $this->assertCount(10, $codes);
        $this->assertTrue($user->fresh()->two_factor_confirmed_at !== null);

        // After login, dashboard rides the challenge.
        $this->post(route('logout'));
        $this->actingAs($user);
        $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));

        // A valid TOTP passes the challenge.
        $this->post(route('two-factor.verify'), ['code' => $this->codeFor($user->fresh())])
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_recovery_codes_work_and_burn(): void
    {
        $user = User::factory()->create();
        app(TwoFactorService::class)->beginEnrollment($user);
        $codes = app(TwoFactorService::class)->confirmEnrollment($user, $this->codeFor($user->fresh()));

        $recovery = $codes[0];

        $this->assertTrue(app(TwoFactorService::class)->verify($user->fresh(), $recovery), 'First use succeeds');

        $this->assertFalse(app(TwoFactorService::class)->verify($user->fresh(), $recovery), 'Second use fails');
    }

    public function test_two_factor_can_be_disabled_with_a_valid_code(): void
    {
        $user = User::factory()->create();
        app(TwoFactorService::class)->beginEnrollment($user);
        app(TwoFactorService::class)->confirmEnrollment($user, $this->codeFor($user->fresh()));

        $user = $user->fresh();
        app(TwoFactorService::class)->markConfirmed();

        $this->actingAs($user)
            ->post(route('two-factor.disable'), ['code' => $this->codeFor($user)])
            ->assertRedirect();

        $this->assertFalse(app(TwoFactorService::class)->isEnabled($user->fresh()));
    }
}
