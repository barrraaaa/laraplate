<?php

namespace App\Modules\Tenancy\Http\Middleware;

use App\Modules\Core\SaaSMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates shared-organization routes out of single-user installations
 * (PRD 5.3: configurable tenancy mode). In single mode these routes
 * respond 404 — the feature does not exist there.
 */
final class EnsureOrganizationsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(SaaSMode::organizationsEnabled(), 404);

        return $next($request);
    }
}
