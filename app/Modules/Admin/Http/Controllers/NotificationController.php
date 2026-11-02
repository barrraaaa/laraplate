<?php

namespace App\Modules\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * In-app notification reads (PRD 5.7): marking read happens without a
 * page reload — the bell lives in the app shell.
 */
class NotificationController extends Controller
{
    public function markRead(Request $request, string $notificationId): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->first()
            ?->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
