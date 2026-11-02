<?php

namespace App\Http\Middleware;

use App\Modules\Core\FeatureManager;
use App\Modules\Core\SaaSMode;
use App\Modules\Tenancy\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $organization = OrganizationContext::organization();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $this->serializeUser($request),
            ],
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'personal' => $organization->isPersonal(),
            ] : null,
            'availableOrganizations' => $this->availableOrganizations($request),
            'notifications' => $this->notifications($request),
            'unreadNotificationsCount' => $request->user()?->unreadNotifications()->count() ?? 0,
            'saas' => [
                'mode' => SaaSMode::current(),
                'features' => app(FeatureManager::class)->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeUser(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'is_platform_admin' => (bool) $user->is_platform_admin,
            // Avatar is private — expose a short-lived signed URL only.
            'avatarUrl' => $user->avatar_path !== null
                ? URL::temporarySignedRoute('files.show', now()->addHour(), ['path' => $user->avatar_path])
                : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function notifications(Request $request): array
    {
        if ($request->user() === null) {
            return [];
        }

        return $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => (string) ($notification->data['title'] ?? 'Notification'),
                'body' => (string) ($notification->data['body'] ?? ''),
                'url' => $notification->data['url'] ?? null,
                'read' => $notification->read_at !== null,
                'createdAt' => $notification->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * Organizations surfaced to the context switcher — shared workspaces
     * plus the (invisible elsewhere) personal organization anchor.
     *
     * @return array<int, array{id: int, name: string, slug: string|null, personal: bool}>
     */
    private function availableOrganizations(Request $request): array
    {
        if ($request->user() === null || ! SaaSMode::organizationsEnabled()) {
            return [];
        }

        return $request->user()
            ->organizations()
            ->get(['organizations.id', 'organizations.name', 'organizations.slug', 'organizations.personal'])
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'personal' => $org->isPersonal(),
            ])
            ->values()
            ->all();
    }
}
