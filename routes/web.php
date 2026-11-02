<?php

use App\Http\Controllers\ProfileController;
use App\Models\Plan;
use App\Modules\Admin\Http\Controllers\AdminController;
use App\Modules\Admin\Http\Controllers\NotificationController;
use App\Modules\Auth\Http\Controllers\ApiTokenController;
use App\Modules\Auth\Http\Controllers\FileController;
use App\Modules\Billing\Http\Controllers\BillingController;
use App\Modules\Billing\Http\Controllers\StripeWebhookController;
use App\Modules\Projects\Http\Controllers\ProjectController;
use App\Modules\Tenancy\Http\Controllers\InvitationController;
use App\Modules\Tenancy\Http\Controllers\MemberController;
use App\Modules\Tenancy\Http\Controllers\OrganizationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'plans' => Plan::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'description', 'features'])
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'features' => $plan->entitlements(),
            ]),
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
    Route::get('/health', [AdminController::class, 'health'])->name('health.index');
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/settings/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
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
