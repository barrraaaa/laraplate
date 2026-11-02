<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Tenancy\Exceptions\LastOwnerRemovalBlockedException;
use App\Modules\Tenancy\Http\Middleware\EnsureOrganizationsEnabled;
use App\Modules\Tenancy\Http\Middleware\SetOrganizationContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'organizations-enabled' => EnsureOrganizationsEnabled::class,
        ]);

        $middleware->web(append: [
            SetOrganizationContext::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Domain rule violations surface as validation feedback, not 500s.
        $exceptions->render(function (LastOwnerRemovalBlockedException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['role' => $e->getMessage()]);
        });
    })->create();
