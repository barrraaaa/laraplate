<?php

namespace App\Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Personal API token management (PRD 5.8) — Sanctum-backed, abilities
 * drawn from config/saas.php -> api_tokens.abilities.
 */
class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('app/Settings/ApiTokens', [
            'tokens' => $request->user()
                ->tokens()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (PersonalAccessToken $token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'lastUsedAt' => $token->last_used_at?->toIso8601String(),
                    'createdAt' => $token->created_at?->toIso8601String(),
                ]),
            'abilities' => (array) config('saas.api_tokens.abilities', ['read']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'abilities' => ['array'],
            'abilities.*' => ['string', Rule::in((array) config('saas.api_tokens.abilities', ['read']))],
        ]);

        $token = $request->user()->createToken(
            $data['name'],
            $data['abilities'] ?? ['read'],
        );

        return back()->with('plainTextToken', $token->plainTextToken);
    }

    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        abort_unless($token->tokenable_id === $request->user()->id, 404);

        $token->delete();

        return back()->with('status', 'token-revoked');
    }
}
