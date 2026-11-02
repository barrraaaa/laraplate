<?php

namespace App\Modules\Auth\Services;

use App\Mail\Auth\MagicLoginMail;
use App\Models\User;
use App\Modules\Auth\Models\MagicLogin;
use App\Modules\Core\FeatureManager;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Magic-link (passwordless) authentication: send, redeem, guard.
 */
final class MagicLinkService
{
    public function __construct(private OrganizationService $organizations) {}

    public function send(string $email): void
    {
        if (! app(FeatureManager::class)->enabled('magic_links')) {
            throw new RuntimeException('Magic links are disabled.');
        }

        $token = Str::random(64);
        $expiresAt = now()->addMinutes($this->expiryMinutes());

        MagicLogin::query()->create([
            'email' => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

        Mail::to($email)->queue(new MagicLoginMail($token, $expiresAt));
    }

    /**
     * Redeems a token: resolves (or auto-creates + verifies) the user,
     * consumes the token, logs in. Email ownership is proven by the link.
     */
    public function redeem(string $token): User
    {
        $magic = MagicLogin::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        throw_unless($magic !== null && $magic->isUsable(), RuntimeException::class, 'This login link is invalid or expired.');

        return DB::transaction(function () use ($magic): User {
            $user = User::query()->where('email', $magic->email)->first();

            if ($user === null) {
                // The link itself proves ownership of the mailbox.
                $user = User::query()->create([
                    'name' => Str::title(strtok($magic->email, '@')),
                    'email' => $magic->email,
                    'password' => bcrypt(Str::random(40)),
                ]);

                // email_verified_at is not mass-assignable by design —
                // the magic link IS the verification.
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $magic->forceFill([
                'used_at' => now(),
                'user_id' => $user->id,
            ])->save();

            $this->organizations->personalOrganizationFor($user);

            return $user;
        });
    }

    private function expiryMinutes(): int
    {
        return (int) config('saas.auth.magic_link_ttl_minutes', 15);
    }
}
