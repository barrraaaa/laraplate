<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Modules\Audit\Services\Auditing;
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
        private Auditing $auditing,
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
        abort_unless($this->providerConfigured('google'), 404);

        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        abort_unless($this->providerConfigured('google'), 404);

        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['google' => 'Could not sign in with Google.']);
        }

        return $this->loginSocialUser(
            $request,
            $socialUser->getEmail(),
            $socialUser->getName(),
            'google',
        );
    }

    public function githubRedirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        abort_unless($this->providerConfigured('github'), 404);

        return Socialite::driver('github')->redirect();
    }

    public function githubCallback(Request $request): RedirectResponse
    {
        abort_unless($this->providerConfigured('github'), 404);

        try {
            $socialUser = Socialite::driver('github')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['github' => 'Could not sign in with GitHub.']);
        }

        // GitHub only returns the email when it is public on the profile.
        if ($socialUser->getEmail() === null || $socialUser->getEmail() === '') {
            return redirect()->route('login')->withErrors([
                'github' => 'GitHub did not share your email — make it public on your profile, then try again.',
            ]);
        }

        return $this->loginSocialUser(
            $request,
            $socialUser->getEmail(),
            $socialUser->getName() ?? $socialUser->getNickname(),
            'github',
        );
    }

    private function loginSocialUser(Request $request, string $email, ?string $name, string $provider): RedirectResponse
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name ?? strtok($email, '@'),
                'password' => bcrypt(Str::random(40)),
                'email_verified_at' => now(),
            ],
        );

        // OAuth-linked accounts get verified mailbox status too.
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->organizations->personalOrganizationFor($user);
        $this->auditing->record('auth.'.$provider.'_login', subject: $user, actor: $user);

        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function providerConfigured(string $provider): bool
    {
        return $this->features->enabled('social_login')
            && filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }
}
