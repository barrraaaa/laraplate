<?php

namespace App\Providers;

use App\Modules\Core\FeatureManager;
use App\Modules\Tenancy\Listeners\CreatePersonalOrganization;
use App\Modules\Tenancy\OrganizationContext;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            FeatureManager::class,
            fn (): FeatureManager => new FeatureManager(config('saas.features')),
        );

        // Scoped: resolved fresh per request (Octane-safe).
        $this->app->scoped(OrganizationContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(Registered::class, CreatePersonalOrganization::class);
    }
}
