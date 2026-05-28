<?php

namespace App\Services;

use App\Jobs\SendBulkPushNotificationJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\Escalation;
use App\Models\EscalationMatrix;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use App\Notifications\IncidentAssignedNotification;
use App\Notifications\IncidentClosedNotification;
use App\Notifications\IncidentEscalatedNotification;
use App\Notifications\IncidentRejectedNotification;
use App\Notifications\IncidentResolvedNotification;
use App\Notifications\MentionNotification;
use App\Notifications\NewCommentNotification;
use App\Notifications\NewIncidentNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    // ==========================================
    // INCIDENT CREATED
    // ==========================================

    /**
     * Notify about new incident creation.
     * Sends to: Department HOD, Supervisors, Admins (for critical)
     */
    public function notifyNewIncident(Incident $incident): void
    {
        $recipients = collect();

        // 1. Notify Department HOD
        $hod = $incident->department->getHeadOfDepartment();
        if ($hod && $hod->id !== $incident->reported_by) {
            $recipients->push($hod);
            Notification::send($hod, new NewIncidentNotification($incident));
        }

        // 2. Notify Department Supervisors
        $supervisors = $incident->department->getSupervisors();
        foreach ($supervisors as $supervisor) {
            if ($supervisor->id !== ($hod->id ?? 0) && $supervisor->id !== $incident->reported_by) {
                $recipients->push($supervisor);
                Notification::send($supervisor, new NewIncidentNotification($incident));
            }
        }

        // 3. For critical/high severity, notify all admins
        if (in_array($incident->severity, ['critical', 'high'])) {
            $admins = User::role(['admin', 'super-admin'])
                ->where('id', '!=', $incident->reported_by)
                ->get();
            foreach ($admins as $admin) {
                $recipients->push($admin);
                Notification::send($admin, new NewIncidentNotification($incident));
            }
        }

        // 4. Queue push notifications
        $this->queuePushNotifications($recipients->unique('id'), [
            'title' => '🚨 New Incident Reported',
            'body' => "{$incident->title}\nSeverity: " . ucfirst($incident->severity) .
                " | Department: {$incident->department->name}",
            'data' => [
                'type' => 'new_incident',
                'incident_id' => (string) $incident->id,
                'click_action' => route('incidents.show', $incident->id),
            ],
        ]);
    }

    // ==========================================
    // INCIDENT ASSIGNED
    // ==========================================

    /**
     * Notify when incident is assigned to a user.
     */
    public function notifyIncidentAssigned(Incident $incident, int $userId): void
    {
        $user = User::find($userId);
        if (!$user || $user->id === auth()->id()) return;

        // Database + Email notification
        Notification::send($user, new IncidentAssignedNotification($incident));

        // Push notification
        $this->queuePushNotification($user, [
            'title' => '📋 Incident Assigned to You',
            'body' => "Incident #{$incident->incident_id}: {$incident->title}",
            'data' => [
                'type' => 'incident_assigned',
                'incident_id' => (string) $incident->id,
                'click_action' => route('incidents.show', $incident->id),
            ],
        ]);
    }

    // ==========================================
    // INCIDENT ESCALATED
    // ==========================================

    /**
     * Notify when incident is escalated.
     */
    public function notifyIncidentEscalated(Incident $incident, Escalation $escalation): void
    {
        $recipients = collect();

        // Notify escalated user
        $escalatedUser = User::find($escalation->escalated_to);
        if ($escalatedUser) {
            $recipients->push($escalatedUser);
            Notification::send($escalatedUser, new IncidentEscalatedNotification($incident, $escalation));
        }

        // Notify target department HOD
        $hod = $escalation->toDepartment->getHeadOfDepartment();
        if ($hod && $hod->id !== ($escalation->escalated_to ?? 0)) {
            $recipients->push($hod);
            Notification::send($hod, new IncidentEscalatedNotification($incident, $escalation));
        }

        // Push notifications
        $this->queuePushNotifications($recipients->unique('id'), [
            'title' => '⬆️ Incident Escalated',
            'body' => "Incident #{$incident->incident_id} requires your attention. Level: {$escalation->level}",
            'data' => [
                'type' => 'incident_escalated',
                'incident_id' => (string) $incident->id,
                'click_action' => route('incidents.show', $incident->id),
            ],
        ]);
    }

    // ==========================================
    // INCIDENT RESOLVED
    // ==========================================

    /**
     * Notify when incident is resolved.
     */
    public function notifyIncidentResolved(Incident $incident): void
    {
        $recipients = collect();

        // Notify reporter
        if ($incident->reporter && $incident->reporter->id !== auth()->id()) {
            $recipients->push($incident->reporter);
            Notification::send($incident->reporter, new IncidentResolvedNotification($incident));
        }

        // Notify HOD
        $hod = $incident->department->getHeadOfDepartment();
        if ($hod && $hod->id !== auth()->id() && $hod->id !== ($incident->reporter->id ?? 0)) {
            $recipients->push($hod);
            Notification::send($hod, new IncidentResolvedNotification($incident));
        }

        // Push notifications
        $this->queuePushNotifications($recipients->unique('id'), [
            'title' => '✅ Incident Resolved',
            'body' => "Incident #{$incident->incident_id} has been resolved.",
            'data' => [
                'type' => 'incident_resolved',
                'incident_id' => (string) $incident->id,
                'click_action' => route('incidents.show', $incident->id),
            ],
        ]);
    }

    // ==========================================
    // INCIDENT CLOSED
    // ==========================================

    /**
     * Notify when incident is closed.
     */
    public function notifyIncidentClosed(Incident $incident): void
    {
        $users = collect([$incident->reporter, $incident->assignedTo, $incident->escalatedTo])
            ->filter()
            ->unique('id');

        foreach ($users as $user) {
            if ($user->id !== auth()->id()) {
                Notification::send($user, new IncidentClosedNotification($incident));
            }
        }

        $this->queuePushNotifications($users, [
            'title' => '🔒 Incident Closed',
            'body' => "Incident #{$incident->incident_id} has been closed.",
            'data' => [
                'type' => 'incident_closed',
                'incident_id' => (string) $incident->id,
                'click_action' => route('incidents.show', $incident->id),
            ],
        ]);
    }

    // ==========================================
    // INCIDENT REOPENED
    // ==========================================

    /**
     * Notify when incident is reopened.
     */
    public function notifyIncidentReopened(Incident $incident): void
    {
        $title = '🔄 Incident Reopened: #' . $incident->incident_id;
        $body = "{$incident->title} has been reopened.";

        $data = [
            'type' => 'incident_reopened',
            'incident_id' => (string) $incident->id,
            'click_action' => route('incidents.show', $incident->id),
        ];

        // Notify assigned user
        if ($incident->assignedTo && $incident->assignedTo->id !== auth()->id()) {
            Notification::send($incident->assignedTo, new NewIncidentNotification($incident));
            $this->queuePushNotification($incident->assignedTo, [
                'title' => $title, 'body' => $body, 'data' => $data,
            ]);
        }

        // Notify HOD
        $hod = $incident->department->getHeadOfDepartment();
        if ($hod && $hod->id !== auth()->id() && $hod->id !== ($incident->assignedTo->id ?? 0)) {
            $this->queuePushNotification($hod, [
                'title' => $title, 'body' => $body, 'data' => $data,
            ]);
        }

        // Notify reporter
        if ($incident->reporter && $incident->reporter->id !== auth()->id()) {
            Notification::send($incident->reporter, new NewIncidentNotification($incident));
            $this->queuePushNotification($incident->reporter, [
                'title' => $title, 'body' => $body, 'data' => $data,
            ]);
        }
    }

    // ==========================================
    // INCIDENT REJECTED
    // ==========================================

    /**
     * Notify when incident is rejected (sent back to reporter).
     */
    public function notifyIncidentRejected(Incident $incident): void
    {
        // Notify reporter
        if ($incident->reporter && $incident->reporter->id !== auth()->id()) {
            Notification::send($incident->reporter, new IncidentRejectedNotification($incident));

            $this->queuePushNotification($incident->reporter, [
                'title' => '❌ Incident Rejected: #' . $incident->incident_id,
                'body' => "Your incident has been rejected. Reason: {$incident->rejection_reason}",
                'data' => [
                    'type' => 'incident_rejected',
                    'incident_id' => (string) $incident->id,
                    'click_action' => route('incidents.show', $incident->id),
                ],
            ]);
        }
    }

    // ==========================================
    // COMMENTS
    // ==========================================

    /**
     * Notify about new comment on incident.
     */
    public function notifyNewComment(Incident $incident, IncidentComment $comment): void
    {
        // Get unique previous commenters (excluding current commenter)
        $previousCommenters = $incident->allComments()
            ->pluck('user_id')
            ->unique()
            ->filter(fn($userId) => $userId !== $comment->user_id);

        foreach ($previousCommenters as $userId) {
            $user = User::find($userId);
            if ($user) {
                Notification::send($user, new NewCommentNotification($incident, $comment));
            }
        }

        // Push notifications to assigned user and reporter
        $pushRecipients = collect([$incident->assignedTo, $incident->reporter])
            ->filter(fn($user) => $user && $user->id !== $comment->user_id)
            ->unique('id');

        $this->queuePushNotifications($pushRecipients, [
            'title' => '💬 New Comment on Incident',
            'body' => "{$comment->user->name}: " . \Str::limit($comment->content, 100),
            'data' => [
                'type' => 'new_comment',
                'incident_id' => (string) $incident->id,
                'comment_id' => (string) $comment->id,
                'click_action' => route('incidents.show', $incident->id) . '#comments',
            ],
        ]);
    }

    /**
     * Notify users mentioned in a comment.
     */
    public function notifyMentionedUsers(Incident $incident, IncidentComment $comment): void
    {
        foreach ($comment->mentioned_users as $user) {
            if ($user->id !== $comment->user_id) {
                Notification::send($user, new MentionNotification($incident, $comment));

                $this->queuePushNotification($user, [
                    'title' => '👋 You Were Mentioned',
                    'body' => "{$comment->user->name} mentioned you in incident #{$incident->incident_id}",
                    'data' => [
                        'type' => 'mentioned',
                        'incident_id' => (string) $incident->id,
                        'comment_id' => (string) $comment->id,
                        'click_action' => route('incidents.show', $incident->id),
                    ],
                ]);
            }
        }
    }

    // ==========================================
    // SLA BREACH & AUTO-ESCALATION
    // ==========================================

    /**
     * Send SLA breach notification.
     */
    public function notifySlaBreach(Incident $incident): void
    {
        $title = '⏰ SLA Breach Warning: #' . $incident->incident_id;
        $body = "{$incident->title} has breached SLA. Breach count: {$incident->sla_breach_count}";

        $data = [
            'type' => 'sla_breach',
            'incident_id' => (string) $incident->id,
            'click_action' => route('incidents.show', $incident->id),
        ];

        // Notify assigned user
        if ($incident->assignedTo) {
            Notification::send($incident->assignedTo, new NewIncidentNotification($incident));
            $this->queuePushNotification($incident->assignedTo, [
                'title' => $title, 'body' => $body, 'data' => $data,
            ]);
        }

        // Notify HOD
        $hod = $incident->department->getHeadOfDepartment();
        if ($hod && $hod->id !== ($incident->assignedTo->id ?? 0)) {
            $this->queuePushNotification($hod, [
                'title' => $title, 'body' => $body, 'data' => $data,
            ]);
        }
    }

    /**
     * Check SLA breach and auto-escalate based on escalation matrix.
     */
    public function notifySlaBreachAndEscalate(Incident $incident): void
    {
        // First, send breach notification
        $this->notifySlaBreach($incident);

        // Check if auto-escalation is needed
        $nextLevel = $incident->getEscalationLevel() + 1;

        $escalationEntry = EscalationMatrix::where('department_id', $incident->department_id)
            ->where(function ($query) use ($incident) {
                $query->where('category_id', $incident->category_id)
                    ->orWhereNull('category_id');
            })
            ->where('level', $nextLevel)
            ->active()
            ->first();

        if ($escalationEntry) {
            // Create escalation record
            $escalation = $incident->escalations()->create([
                'escalated_by' => 1, // System user ID
                'escalated_to' => $escalationEntry->escalate_to_user_id,
                'from_department_id' => $incident->department_id,
                'to_department_id' => $escalationEntry->escalate_to_department_id,
                'level' => $nextLevel,
                'reason' => 'Auto-escalated: SLA breach count reached ' . $incident->sla_breach_count,
                'status' => 'pending',
            ]);

            // Update incident
            $incident->update([
                'status' => 'escalated',
                'escalated_to' => $escalationEntry->escalate_to_user_id,
                'escalated_at' => now(),
            ]);

            // Send escalation notifications
            $this->notifyIncidentEscalated($incident, $escalation);

            Log::info('Auto-escalated incident via escalation matrix', [
                'incident_id' => $incident->incident_id,
                'level' => $nextLevel,
                'escalated_to' => $escalationEntry->escalate_to_user_id,
                'matrix_id' => $escalationEntry->id,
            ]);
        } else {
            Log::info('No escalation matrix found for level ' . $nextLevel, [
                'incident_id' => $incident->incident_id,
                'department_id' => $incident->department_id,
                'category_id' => $incident->category_id,
            ]);
        }
    }

    // ==========================================
    // PUSH NOTIFICATION HELPERS
    // ==========================================

    /**
     * Queue push notification for a single user.
     */
    protected function queuePushNotification(User $user, array $notificationData): void
    {
        // Check if user has FCM token
        if (empty($user->fcm_token)) {
            Log::debug('Skip push: User has no FCM token', ['user_id' => $user->id]);
            return;
        }

        // Check user preferences
        $preferences = $user->preferences ?? [];
        $pushEnabled = $preferences['push_notifications'] ?? true;
        if (!$pushEnabled) {
            Log::debug('Skip push: User disabled push notifications', ['user_id' => $user->id]);
            return;
        }

        SendPushNotificationJob::dispatch(
            $user,
            $notificationData['title'] ?? 'IRMSystem Notification',
            $notificationData['body'] ?? 'You have a new notification',
            $notificationData['data'] ?? []
        )->onQueue('notifications');

        Log::debug('Push notification queued', [
            'user_id' => $user->id,
            'title' => $notificationData['title'] ?? 'N/A',
        ]);
    }

    /**
     * Queue push notifications for multiple users.
     */
    protected function queuePushNotifications($users, array $notificationData): void
    {
        $tokens = $users->pluck('fcm_token')->filter()->values()->toArray();

        if (!empty($tokens)) {
            SendBulkPushNotificationJob::dispatch(
                $tokens,
                $notificationData['title'] ?? 'IRMSystem Notification',
                $notificationData['body'] ?? 'You have a new notification',
                $notificationData['data'] ?? []
            )->onQueue('notifications');

            Log::debug('Bulk push notifications queued', [
                'token_count' => count($tokens),
                'title' => $notificationData['title'] ?? 'N/A',
            ]);
        }
    }
}
