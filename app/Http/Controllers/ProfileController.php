<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Modules\Audit\Services\Auditing;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('app/Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'notificationPreferences' => $request->user()->notification_preferences ?? [],
        ]);
    }

    /**
     * Update the user's profile information (name, email, avatar).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $request->validate(['avatar' => ['image', 'max:2048']]);
            $this->storeAvatar($user, $request->file('avatar'));
        }

        unset($data['avatar']);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        app(Auditing::class)->record('auth.account_deleted', subject: $user, actor: $user);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Notification preferences (PRD 5.7 P1): per-user email channels.
     */
    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_success' => ['required', 'boolean'],
            'payment_failure' => ['required', 'boolean'],
            'product_news' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill([
            'notification_preferences' => $data,
        ])->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Private-disk avatar storage (PRD 5.10): unguessable name, served
     * exclusively through signed URLs via files.show.
     */
    private function storeAvatar(User $user, UploadedFile $file): void
    {
        $disk = Storage::disk('private');

        if ($user->avatar_path !== null) {
            $disk->delete($user->avatar_path);
        }

        $path = $disk->putFileAs(
            'avatars',
            $file,
            $user->id.'-'.Str::random(24).'.'.$file->guessExtension(),
        );

        if ($path === false) {
            return;
        }

        $user->forceFill(['avatar_path' => $path]);
    }
}
