<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates protected areas behind a completed 2FA challenge (PRD 5.1 P1).
 * Users without 2FA pass through untouched.
 */
final class TwoFactorChallenge
{
    private const BYPASS_ROUTES = [
        'two-factor.challenge',
        'two-factor.verify',
        'logout',
        'verification.notice',
        'verification.verify',
        'verification.send',
        'password.confirm',
        'password.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $twoFactor = app(TwoFactorService::class);

        if (
            $twoFactor->isEnabled($user)
            && ! $twoFactor->isChallengePassed()
            && ! in_array($request->route()?->getName(), self::BYPASS_ROUTES, true)
        ) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
