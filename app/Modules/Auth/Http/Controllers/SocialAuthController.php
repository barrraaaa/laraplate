<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\Services\MagicLinkService;
use App\Modules\Core\FeatureManager;
use App\Modules\Tenancy\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;

/**
 * Passwordless auth: magic links (email token) + Google OAuth.
 * Either can be disabled via config/saas.php features.
 */
class SocialAuthController extends Controller
{
    public function __construct(
        private MagicLinkService $magicLinks,
        private OrganizationService $organizations,
        private FeatureManager $features,
    ) {}

    public function send(Request $request): RedirectResponse
    {
        abort_unless($this->features->enabled('magic_links'), 404);

        $request->validate(['email' => ['required', 'email']]);

        $this->magicLinks->send($request->string('email')->toString());

        // Identical response whether or not the mailbox exists — no
        // account enumeration.
        return back()->with('status', 'magic-link-sent');
    }

    public function consume(Request $request, string $token): RedirectResponse
    {
        abort_unless($this->features->enabled('magic_links'), 404);

        try {
            $user = $this->magicLinks->redeem($token);
        } catch (RuntimeException) {
            return redirect()->route('login')->withErrors([
                'magic' => 'This login link is invalid or has expired. Request a new one.',
            ]);
        }

        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function googleRedirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        abort_unless($this->googleConfigured(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        abort_unless($this->googleConfigured(), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['google' => 'Could not sign in with Google.']);
        }

        $user = User::query()->firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName() ?? strtok($googleUser->getEmail(), '@'),
                'password' => bcrypt(Str::random(40)),
                'email_verified_at' => now(),
            ],
        );

        // Accounts linked via Google get verified mailbox status too.
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->organizations->personalOrganizationFor($user);

        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function googleConfigured(): bool
    {
        return $this->features->enabled('social_login')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
