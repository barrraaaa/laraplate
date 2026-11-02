<?php

namespace App\Modules\Tenancy\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Modules\Tenancy\OrganizationContext;
use App\Modules\Tenancy\Services\OrganizationService;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active organization for the request into OrganizationContext.
 *
 * Order: session selection wins (the user's explicit context switch),
 * otherwise the personal organization (auto-created as an invariant).
 * Guests skip resolution entirely. Also binds the spatie permissions team
 * so that `$user->can('…')` resolves against the active organization.
 */
final class SetOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var OrganizationContext $context */
        $context = app(OrganizationContext::class);
        $context->set(null);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        if ($user = $request->user()) {
            $context->set($organization = $this->resolve($request, $user));

            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        }

        return $next($request);
    }

    private function resolve(Request $request, User $user): Organization
    {
        $selectedId = $request->session()->get('current_organization_id');

        if ($selectedId !== null) {
            $selected = $user->organizations()->whereKey($selectedId)->first();

            if ($selected !== null) {
                return $selected;
            }
        }

        return app(OrganizationService::class)->personalOrganizationFor($user);
    }
}
