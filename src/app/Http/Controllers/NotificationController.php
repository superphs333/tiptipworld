<?php

namespace App\Http\Controllers;

use App\Services\UserNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(
        Request $request,
        string $notificationId,
        UserNotificationService $service
    ): RedirectResponse {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $service->markAsRead($user, $notificationId);

        return back();
    }

    public function markAllAsRead(
        Request $request,
        UserNotificationService $service
    ): RedirectResponse {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $service->markAllAsRead($user);

        return back();
    }
}
