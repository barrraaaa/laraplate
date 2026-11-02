<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Modules\Admin\Http\Controllers\AdminController;
use App\Modules\Admin\Http\Controllers\CouponAdminController;
use App\Modules\Admin\Http\Controllers\NotificationController;
use App\Modules\Auth\Http\Controllers\ApiTokenController;
use App\Modules\Auth\Http\Controllers\FileController;
use App\Modules\Auth\Http\Controllers\SocialAuthController;
use App\Modules\Auth\Http\Controllers\TwoFactorController;
use App\Modules\Billing\Http\Controllers\BillingController;
use App\Modules\Billing\Http\Controllers\StripeWebhookController;
use App\Modules\Blog\Http\Controllers\BlogAdminController;
use App\Modules\Blog\Http\Controllers\BlogController;
use App\Modules\Projects\Http\Controllers\ProjectController;
use App\Modules\Tenancy\Http\Controllers\InvitationController;
use App\Modules\Tenancy\Http\Controllers\MemberController;
use App\Modules\Tenancy\Http\Controllers\OrganizationController;
use App\Modules\Tenancy\Http\Controllers\OrganizationRoleController;
use App\Modules\Webhooks\Http\Controllers\WebhookController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'store' => [
            'starter' => config('saas.store.starter_url'),
            'allIn' => config('saas.store.all_in_url'),
        ],
        'communityUrl' => config('saas.community.discord_url'),
    ]);
});

Route::get('/terms', function () {
    return Inertia::render('legal/Terms');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('legal/Privacy');
})->name('privacy');

Route::get('/dashboard', function () {
    return Inertia::render('app/Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/notification-preferences', [ProfileController::class, 'updateNotificationPreferences'])->name('profile.notification-preferences');
});

/*
|--------------------------------------------------------------------------
| Shared organizations (PRD 5.3) — team & hybrid modes only.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'organizations-enabled'])->group(function () {
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::patch('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    Route::post('/organizations/{organization}/switch', [OrganizationController::class, 'switchOrganizations'])->name('organizations.switch');

    Route::patch('/organizations/{organization}/members/{member}', [MemberController::class, 'update'])->name('organizations.members.update');
    Route::delete('/organizations/{organization}/members/{member}', [MemberController::class, 'destroy'])->name('organizations.members.destroy');
    Route::post('/organizations/{organization}/transfer-ownership', [MemberController::class, 'transferOwnership'])->name('organizations.transfer-ownership');

    Route::get('/organizations/{organization}/roles', [OrganizationRoleController::class, 'index'])->name('organizations.roles.index');
    Route::post('/organizations/{organization}/roles', [OrganizationRoleController::class, 'store'])->name('organizations.roles.store');
    Route::patch('/organizations/{organization}/roles/{role}', [OrganizationRoleController::class, 'update'])->name('organizations.roles.update');
    Route::delete('/organizations/{organization}/roles/{role}', [OrganizationRoleController::class, 'destroy'])->name('organizations.roles.destroy');
    Route::post('/organizations/{organization}/members/{member}/role-assignment', [OrganizationRoleController::class, 'assign'])->name('organizations.members.assign-role');

    Route::post('/organizations/{organization}/invitations', [InvitationController::class, 'store'])->name('organizations.invitations.store');
    Route::post('/organizations/{organization}/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('organizations.invitations.resend');
    Route::delete('/organizations/{organization}/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('organizations.invitations.destroy');
});

// Invitation links — the 64-char random token (stored hashed) is the
// secret; links are unsigned so they survive email client mutations.
Route::get('/invitations/{token}', [InvitationController::class, 'accept'])
    ->name('invitations.accept');

Route::post('/invitations/{token}/decline', [InvitationController::class, 'decline'])
    ->middleware('auth')
    ->name('invitations.decline');

/*
|--------------------------------------------------------------------------
| Example module — Projects (PRD 5.12): the documented extension pattern.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
});

/*
|--------------------------------------------------------------------------
| Passwordless auth: magic links + Google OAuth (ShipFast pack).
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::post('/auth/magic/send', [SocialAuthController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('auth.magic.send');

    Route::get('/auth/magic/{token}', [SocialAuthController::class, 'consume'])
        ->name('auth.magic.consume');

    Route::get('/auth/google/redirect', [SocialAuthController::class, 'googleRedirect'])
        ->name('auth.google.redirect');

    Route::get('/auth/google/callback', [SocialAuthController::class, 'googleCallback'])
        ->name('auth.google.callback');
});

/*
|--------------------------------------------------------------------------
| Blog (SEO & blog module) + sitemap.
|--------------------------------------------------------------------------
*/

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/deals', function () {
    return Inertia::render('Deals');
})->name('deals');

/*
|--------------------------------------------------------------------------
| Admin panel (PRD 5.11) — platform administration.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'platform-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/organizations', [AdminController::class, 'organizations'])->name('organizations.index');
    Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('subscriptions.index');
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions.index');
    Route::get('/invoices', [AdminController::class, 'invoices'])->name('invoices.index');
    Route::get('/activity', [AdminController::class, 'activity'])->name('activity.index');
    Route::get('/activity/export', [AdminController::class, 'exportActivity'])->name('activity.export');
    Route::get('/health', [AdminController::class, 'health'])->name('health.index');
    Route::get('/api-tokens', [AdminController::class, 'apiTokens'])->name('api-tokens.index');
    Route::delete('/api-tokens/{token}', [AdminController::class, 'revokeToken'])->name('api-tokens.revoke');
    Route::get('/notifications', [AdminController::class, 'notifications'])->name('notifications.index');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings.index');
    Route::post('/settings/maintenance', [AdminController::class, 'toggleMaintenance'])->name('settings.maintenance');
    Route::get('/security', [AdminController::class, 'security'])->name('security.index');
    Route::get('/webhooks', [AdminController::class, 'webhooks'])->name('webhooks.index');

    Route::get('/coupons', [CouponAdminController::class, 'index'])->name('coupons.index');
    Route::post('/coupons', [CouponAdminController::class, 'store'])->name('coupons.store');
    Route::patch('/coupons/{coupon}', [CouponAdminController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [CouponAdminController::class, 'destroy'])->name('coupons.destroy');

    Route::get('/blog', [BlogAdminController::class, 'index'])->name('blog.index');
    Route::get('/blog/create', [BlogAdminController::class, 'create'])->name('blog.create');
    Route::post('/blog', [BlogAdminController::class, 'store'])->name('blog.store');
    Route::get('/blog/{post}/edit', [BlogAdminController::class, 'edit'])->name('blog.edit');
    Route::patch('/blog/{post}', [BlogAdminController::class, 'update'])->name('blog.update');
    Route::delete('/blog/{post}', [BlogAdminController::class, 'destroy'])->name('blog.destroy');
});

/*
|--------------------------------------------------------------------------
| In-app notifications + personal API tokens.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

/*
|--------------------------------------------------------------------------
| Two-factor authentication (PRD 5.1 P1) — TOTP + recovery codes.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify');

    Route::get('/settings/two-factor', [TwoFactorController::class, 'index'])->name('two-factor.index');
    Route::post('/settings/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/settings/two-factor/regenerate', [TwoFactorController::class, 'regenerate'])->name('two-factor.regenerate');
    Route::post('/settings/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/settings/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    Route::get('/settings/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/settings/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
    Route::patch('/settings/webhooks/{endpoint}', [WebhookController::class, 'update'])->name('webhooks.update');
    Route::post('/settings/webhooks/{endpoint}/regenerate-secret', [WebhookController::class, 'regenerateSecret'])->name('webhooks.regenerate-secret');
    Route::delete('/settings/webhooks/{endpoint}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/settings/webhooks/{delivery}/retry', [WebhookController::class, 'retry'])->name('webhooks.retry');
});

// Private files served through signed, expiring URLs (PRD 5.10).
Route::get('/files/{path}', [FileController::class, 'show'])
    ->middleware('signed')
    ->name('files.show');

// Stripe webhooks — signature-verified by Cashier; exempt from CSRF.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

/*
|--------------------------------------------------------------------------
| Billing (PRD 5.5) — the active organization is the billable owner.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

require __DIR__.'/auth.php';
