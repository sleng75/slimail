<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display notifications page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get notifications (using Laravel's built-in notification system)
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn($notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->format('d/m/Y H:i'),
                'time_ago' => $notification->created_at->diffForHumans(),
            ]);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    /**
     * Delete all read notifications.
     */
    public function destroyRead(Request $request)
    {
        $request->user()->readNotifications()->delete();

        return back()->with('success', 'Notifications lues supprimées.');
    }
}
