<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display all notifications.
     */
    public function index()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $notification = DatabaseNotification::findOrFail($id);
        $user = Auth::user();

        // Check ownership
        if ($notification->notifiable_id !== $user->id ||
            $notification->notifiable_type !== get_class($user)) {
            abort(403);
        }

        $notification->markAsRead();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.'
            ]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.'
            ]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a notification.
     */
    public function delete($id)
    {
        $notification = DatabaseNotification::findOrFail($id);
        $user = Auth::user();

        if ($notification->notifiable_id !== $user->id ||
            $notification->notifiable_type !== get_class($user)) {
            abort(403);
        }

        $notification->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted.'
            ]);
        }

        return back()->with('success', 'Notification deleted.');
    }

    /**
     * Get unread notifications count (AJAX).
     */
    public function unreadCount()
    {
        $count = Auth::user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get latest notifications for dropdown (AJAX).
     */
    public function latest()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'general',
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'incident_id' => $notification->data['incident_id'] ?? null,
                    'icon' => $this->getNotificationIcon($notification->data['type'] ?? 'general'),
                    'color' => $this->getNotificationColor($notification->data['type'] ?? 'general'),
                    'time' => $notification->created_at->diffForHumans(),
                    'read' => !is_null($notification->read_at),
                    'url' => $notification->data['url'] ?? '#',
                ];
            });

        return response()->json($notifications);
    }

    /**
     * Handle notification click - mark as read and redirect.
     */
    public function handleClick($id)
    {
        $notification = DatabaseNotification::findOrFail($id);
        $user = Auth::user();

        if ($notification->notifiable_id !== $user->id ||
            $notification->notifiable_type !== get_class($user)) {
            abort(403);
        }

        $notification->markAsRead();

        $data = $notification->data;
        $redirectUrl = route('dashboard');

        // Determine redirect based on notification type
        switch ($data['type'] ?? '') {
            case 'new_incident':
            case 'incident_assigned':
            case 'incident_escalated':
            case 'incident_resolved':
            case 'incident_closed':
            case 'incident_reopened':
            case 'new_comment':
            case 'mentioned':
                if (isset($data['incident_id'])) {
                    $redirectUrl = route('incidents.show', $data['incident_id']);
                }
                break;

            case 'status_update':
                $redirectUrl = $data['url'] ?? route('dashboard');
                break;

            default:
                $redirectUrl = $data['url'] ?? route('dashboard');
        }

        return redirect($redirectUrl);
    }

    /**
     * Get notification icon based on type.
     */
    private function getNotificationIcon(string $type): string
    {
        return match ($type) {
            'new_incident' => 'fa-exclamation-triangle',
            'incident_assigned' => 'fa-user-plus',
            'incident_escalated' => 'fa-arrow-up',
            'incident_resolved' => 'fa-check-circle',
            'incident_closed' => 'fa-lock',
            'incident_reopened' => 'fa-redo',
            'new_comment' => 'fa-comment',
            'mentioned' => 'fa-at',
            'status_update' => 'fa-info-circle',
            default => 'fa-bell',
        };
    }

    /**
     * Get notification color based on type.
     */
    private function getNotificationColor(string $type): string
    {
        return match ($type) {
            'new_incident', 'incident_escalated' => '#EF4444',
            'incident_assigned' => '#3B82F6',
            'incident_resolved' => '#10B981',
            'incident_closed' => '#6B7280',
            'incident_reopened' => '#F59E0B',
            'new_comment', 'mentioned' => '#8B5CF6',
            'status_update' => '#6366F1',
            default => '#6B7280',
        };
    }
}
