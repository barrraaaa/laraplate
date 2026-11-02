<?php

namespace App\Providers;

use App\Models\Organization;
use App\Modules\Audit\Support\AuditEventsSubscriber;
use App\Modules\Billing\Providers\BillingGateway;
use App\Modules\Billing\Providers\StripeGateway;
use App\Modules\Core\FeatureManager;
use App\Modules\Tenancy\Listeners\AcceptPendingInvitation;
use App\Modules\Tenancy\Listeners\CreatePersonalOrganization;
use App\Modules\Tenancy\Listeners\WelcomeNewUser;
use App\Modules\Tenancy\OrganizationContext;
use App\Modules\Tenancy\Policies\OrganizationPolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

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

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user() !== null ? (string) $request->user()->id : (string) $request->ip(),
            );
        });

        Event::listen(Registered::class, CreatePersonalOrganization::class);
        Event::listen(Registered::class, AcceptPendingInvitation::class);
        Event::listen(Registered::class, WelcomeNewUser::class);
        Event::subscribe(AuditEventsSubscriber::class);

        // Module policies live outside App\Policies — register explicitly.
        Gate::policy(Organization::class, OrganizationPolicy::class);

        // System health checks (admin System Health screen + CI).
        Health::checks([
            EnvironmentCheck::new(),
            DebugModeCheck::new(),
            DatabaseCheck::new(),
            CacheCheck::new(),
            QueueCheck::new(),
            UsedDiskSpaceCheck::new(),
        ]);
    }
}
