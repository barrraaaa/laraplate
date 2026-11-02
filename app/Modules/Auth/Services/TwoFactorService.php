<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OTPHP\TOTP;
use RuntimeException;

/**
 * TOTP two-factor authentication (PRD 5.1 P1): enabling, verification,
 * recovery codes and QR provisioning. Depends on otphp (TOTP math) and
 * bacon/bacon-qr-code (SVG QR) — no third-party service involved.
 */
final class TwoFactorService
{
    private const SESSION_CONFIRMED = 'two_factor.confirmed';

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    public function isPendingConfirmation(User $user): bool
    {
        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at === null;
    }

    /**
     * Starts enrollment: stores an unconfirmed secret and returns the
     * provisioning data (SVG QR + secret) for the UI.
     *
     * @return array{secret: string, qrCodeSvg: string}
     */
    public function beginEnrollment(User $user): array
    {
        $secret = $this->randomBase32Secret();
        $totp = TOTP::createFromSecret($secret);
        $totp->setIssuer((string) config('app.name'));
        $totp->setLabel($user->email);

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return [
            'secret' => $secret,
            'qrCodeSvg' => $this->qrCodeSvg($totp->getProvisioningUri()),
        ];
    }

    /**
     * RFC base32 secret (otphp's own factories behave inconsistently
     * across builds — generate valid Base32 ourselves).
     */
    private function randomBase32Secret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Confirms enrollment with a fresh TOTP code and generates recovery
     * codes (returned once, stored hashed).
     *
     * @return array<int, string>
     */
    public function confirmEnrollment(User $user, string $code): array
    {
        $this->guardPending($user);

        if (! $this->totpFor($user, $user->two_factor_secret)->verify($code, null, 1)) {
            throw new RuntimeException('The verification code is invalid.');
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($recoveryCodes),
        ])->save();

        $this->markConfirmed();

        return $recoveryCodes;
    }

    public function verify(User $user, string $code): bool
    {
        if (! $this->isEnabled($user)) {
            return true;
        }

        return $this->totpFor($user, $user->two_factor_secret)->verify($code, null, 1)
            || $this->consumeRecoveryCode($user, $code);
    }

    /**
     * Regenerates the recovery-code set (previous codes are discarded).
     *
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        throw_unless($this->isEnabled($user), RuntimeException::class, 'Two-factor is not enabled.');

        $codes = $this->generateRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => $this->hashRecoveryCodes($codes)])->save();

        return $codes;
    }

    public function disable(User $user, string $code): void
    {
        throw_unless($this->verify($user, $code), RuntimeException::class, 'The code is invalid.');

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    public function markConfirmed(): void
    {
        session()->put(self::SESSION_CONFIRMED, true);
    }

    public function isChallengePassed(): bool
    {
        return (bool) session(self::SESSION_CONFIRMED);
    }

    private function guardPending(User $user): void
    {
        throw_unless($this->isPendingConfirmation($user), RuntimeException::class, 'No pending two-factor enrollment.');
    }

    private function totpFor(User $user, string $secret): TOTP
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setIssuer((string) config('app.name'));
        $totp->setLabel($user->email);

        return $totp;
    }

    private function qrCodeSvg(string $provisioningUri): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220, 0),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($provisioningUri);
    }

    /**
     * @return array<int, string>
     */
    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 10))
            ->map(fn (): string => strtoupper(Str::random(8)).'-'.strtoupper(Str::random(4)))
            ->all();
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    private function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn (string $code): string => Hash::make($code), $codes);
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes;

        if ($codes === null || $codes === []) {
            return false;
        }

        foreach ($codes as $index => $hashed) {
            if (Hash::check($code, $hashed)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}
