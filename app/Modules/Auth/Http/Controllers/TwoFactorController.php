<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\TwoFactorService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use OTPHP\TOTP;
use RuntimeException;

/**
 * 2FA lifecycle: challenge screen, enrollment (QR + confirm + recovery
 * codes) and settings actions (regenerate / disable).
 */
class TwoFactorController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private TwoFactorService $twoFactor) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $pending = $this->twoFactor->isPendingConfirmation($user);

        $qrSvg = null;
        $secret = null;

        if ($pending && $user->two_factor_secret !== null) {
            $totp = TOTP::createFromSecret($user->two_factor_secret);
            $totp->setIssuer((string) config('app.name'));
            $totp->setLabel($user->email);

            $renderer = new ImageRenderer(
                new RendererStyle(220, 0),
                new SvgImageBackEnd,
            );

            $qrSvg = (new Writer($renderer))->writeString($totp->getProvisioningUri());
            $secret = $user->two_factor_secret;
        }

        return Inertia::render('app/Settings/TwoFactor', [
            'enabled' => $this->twoFactor->isEnabled($user),
            'pending' => $pending,
            'qrCodeSvg' => $qrSvg,
            'secret' => $secret,
            'recoveryCodes' => session('recoveryCodes', []),
            'status' => session('status'),
        ]);
    }

    public function challenge(Request $request): Response
    {
        return Inertia::render('auth/TwoFactor/Challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $user = $request->user();

        if ($this->twoFactor->verify($user, $data['code'])) {
            $this->twoFactor->markConfirmed();

            return redirect()->intended(route('dashboard'));
        }

        return redirect()
            ->route('two-factor.challenge')
            ->withErrors(['code' => 'The code is invalid.']);
    }

    public function enable(Request $request): RedirectResponse
    {
        $this->twoFactor->beginEnrollment($request->user());

        return back()->with('status', 'two-factor-setup');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $recoveryCodes = $this->twoFactor->confirmEnrollment($request->user(), $data['code']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('recoveryCodes', $recoveryCodes);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        return back()->with('recoveryCodes', $this->twoFactor->regenerateRecoveryCodes($request->user()));
    }

    public function disable(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        try {
            $this->twoFactor->disable($request->user(), $data['code']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'two-factor-disabled');
    }
}
