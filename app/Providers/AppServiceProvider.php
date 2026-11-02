<?php

namespace App\Providers;

use App\Models\Organization;
use App\Modules\Billing\Providers\BillingGateway;
use App\Modules\Billing\Providers\StripeGateway;
use App\Modules\Core\FeatureManager;
use App\Modules\Tenancy\Listeners\AcceptPendingInvitation;
use App\Modules\Tenancy\Listeners\CreatePersonalOrganization;
use App\Modules\Tenancy\OrganizationContext;
use App\Modules\Tenancy\Policies\OrganizationPolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        // Billing provider adapter — swap implementations (or fake it in
        // tests) without touching domain code.
        $this->app->bind(BillingGateway::class, StripeGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(Registered::class, CreatePersonalOrganization::class);
        Event::listen(Registered::class, AcceptPendingInvitation::class);

        // Module policies live outside App\Policies — register explicitly.
        Gate::policy(Organization::class, OrganizationPolicy::class);
    }
}
