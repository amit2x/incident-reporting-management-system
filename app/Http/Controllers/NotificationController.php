<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\BroadcastMessage;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ============================================================
    // EXISTING METHODS — UNCHANGED
    // ============================================================

    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(20);
        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAsRead($id)
    {
        $notification = DatabaseNotification::findOrFail($id);
        $user = Auth::user();

        if ($notification->notifiable_id !== $user->id ||
            $notification->notifiable_type !== get_class($user)) {
            abort(403);
        }

        $notification->markAsRead();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Notification marked as read.']);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

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
            return response()->json(['success' => true, 'message' => 'Notification deleted.']);
        }

        return back()->with('success', 'Notification deleted.');
    }

    public function unreadCount()
    {
        if (! Auth::check()) {
            return response()->json(['count' => 0]);
        }

        return response()->json(['count' => Auth::user()->unreadNotifications()->count()]);
    }

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
                    'read' => ! is_null($notification->read_at),
                    'url' => $notification->data['url'] ?? '#',
                ];
            });

        return response()->json($notifications);
    }

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

            case 'broadcast':
                // Broadcast notifications don't redirect anywhere — just mark as read
                $redirectUrl = $data['url'] ?? route('notifications.index');
                break;

            default:
                $redirectUrl = $data['url'] ?? route('dashboard');
        }

        return redirect($redirectUrl);
    }

    // ============================================================
    // NEW METHODS — BROADCAST (Nodal Officer only)
    // ============================================================

    /**
     * Show the form for composing a broadcast notification.
     * Only users with `notifications.broadcast` permission can access.
     */
    public function createBroadcast()
    {
        $this->authorize('notifications.broadcast');

        $user = Auth::user();

        // Preview recipient count
        $recipientCount = User::where('airport_id', $user->airport_id)
            ->where('status', 'active')
            ->count();

        return view('notifications.broadcast', compact('recipientCount'));
    }

    /**
     * Send a free-text broadcast to all active users of the Nodal Officer's airport.
     */
    public function sendBroadcast(Request $request)
    {
        $this->authorize('notifications.broadcast');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:2000'],
            'send_email' => ['nullable', 'boolean'],
            'send_push' => ['nullable', 'boolean'],
        ]);

        $sender = Auth::user();

        // Get all active users in the same airport (excluding the sender)
        $recipients = User::where('airport_id', $sender->airport_id)
            ->where('status', 'active')
            ->where('id', '!=', $sender->id)
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No active users to notify.');
        }

        // Determine channels
        $channels = ['database'];
        if (! empty($data['send_email'])) {
            $channels[] = 'mail';
        }
        if (! empty($data['send_push'])) {
            // Your app uses FCM — append your FCM channel here
            // $channels[] = \App\Notifications\Channels\FcmChannel::class; // or 'fcm'
            $channels[] = 'fcm';
        }

        Notification::send($recipients, new BroadcastMessage(
            title: $data['title'],
            message: $data['message'],
            senderId: $sender->id,
            senderName: $sender->name,
            airportId: $sender->airport_id,
            channels: $channels,
        ));

        return redirect()
            ->route('notifications.index')
            ->with('success', "Notification sent to {$recipients->count()} users.");
    }

    // ============================================================
    // HELPERS — UNCHANGED (with broadcast added)
    // ============================================================

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
            'broadcast' => 'fa-bullhorn',  // ✅ NEW
            default => 'fa-bell',
        };
    }

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
            'broadcast' => '#F59E0B',  // ✅ NEW — amber, matches bullhorn vibe
            default => '#6B7280',
        };
    }
}
