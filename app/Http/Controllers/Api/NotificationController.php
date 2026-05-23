<?php
// app/Http/Controllers/Api/NotificationController.php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends BaseApiController
{
    /**
     * Get all notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser();

        $notifications = $user->notifications()
            ->latest()
            ->paginate($request->get('per_page', 20));

        $notifications->through(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'general',
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'incident_id' => $notification->data['incident_id'] ?? null,
                'url' => $notification->data['url'] ?? '#',
                'read' => !is_null($notification->read_at),
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                'time_ago' => $notification->created_at->diffForHumans(),
            ];
        });

        return $this->paginatedResponse($notifications);
    }

    /**
     * Get unread count
     */
    public function unreadCount(): JsonResponse
    {
        $count = $this->getUser()->unreadNotifications()->count();

        return $this->successResponse(['count' => $count]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = DatabaseNotification::findOrFail($id);
        $user = $this->getUser();

        if ($notification->notifiable_id !== $user->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $notification->markAsRead();

        return $this->successResponse(null, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $this->getUser()->unreadNotifications->markAsRead();

        return $this->successResponse(null, 'All notifications marked as read');
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web',
        ]);

        $this->getUser()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return $this->successResponse(null, 'Subscribed to push notifications');
    }
}