<?php

namespace App\Http\Middleware;

use App\Modules\Core\FeatureManager;
use App\Modules\Core\SaaSMode;
use App\Modules\Tenancy\OrganizationContext;
use Illuminate\Http\Request;
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
                'user' => $request->user(),
            ],
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'personal' => $organization->isPersonal(),
            ] : null,
            'availableOrganizations' => $this->availableOrganizations($request),
            'saas' => [
                'mode' => SaaSMode::current(),
                'features' => app(FeatureManager::class)->all(),
            ],
        ];
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
