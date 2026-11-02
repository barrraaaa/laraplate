<?php

namespace App\Modules\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform-admin gate for the admin panel (separate from tenant roles —
 * PRD 5.11: platform administration is not organization administration).
 */
final class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_platform_admin === true, 403);

        return $next($request);
    }
}
