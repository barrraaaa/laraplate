<?php

use App\Http\Controllers\ProfileController;
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
    ]);
});

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

require __DIR__.'/auth.php';
