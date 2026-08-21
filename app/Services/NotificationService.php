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
use App\Notifications\IncidentReopenedNotification;
use App\Notifications\IncidentResolvedNotification;
use App\Notifications\EscalationRespondedNotification;
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
        $reporterId = $incident->reported_by;

        // 1. Notify Department HOD
        $hod = $incident->department ? $incident->department->getHeadOfDepartment() : null;
        if ($hod && $hod->id !== $reporterId) {
            $recipients->push($hod);
            $hod->notify(new NewIncidentNotification($incident));
        }

        // 2. Notify Department Supervisors
        $supervisors = $incident->department ? $incident->department->getSupervisors() : collect();
        foreach ($supervisors as $supervisor) {
            if ($supervisor->id !== ($hod->id ?? 0) && $supervisor->id !== $reporterId) {
                $recipients->push($supervisor);
                $supervisor->notify(new NewIncidentNotification($incident));
            }
        }

        // 3. For critical/high severity, notify all admins
        if (in_array($incident->severity, ['critical', 'high'])) {
            $admins = User::role(['admin', 'super-admin'])
                ->where('id', '!=', $reporterId)
                ->get();
            foreach ($admins as $admin) {
                $recipients->push($admin);
                $admin->notify(new NewIncidentNotification($incident));
            }
        }

        // 4. Notify assigned user if different
        if ($incident->assigned_to && $incident->assigned_to !== $reporterId) {
            $assignedUser = User::find($incident->assigned_to);
            if ($assignedUser && !$recipients->contains('id', $assignedUser->id)) {
                $recipients->push($assignedUser);
                $assignedUser->notify(new NewIncidentNotification($incident));
            }
        }

        // 5. Queue push notifications
        $departmentName = $incident->department ? $incident->department->name : 'N/A';
        $this->queuePushNotifications($recipients->unique('id'), [
            'title' => '🚨 New Incident Reported',
            'body' => "{$incident->title}\nSeverity: " . ucfirst($incident->severity) .
                " | Department: {$departmentName}",
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
        if (!$user) {
            Log::warning('User not found for assignment notification', [
                'user_id' => $userId,
                'incident_id' => $incident->id,
            ]);
            return;
        }

        // Don't send notification to self
        if ($user->id === auth()->id()) {
            Log::debug('Skipping self-notification for assignment', [
                'user_id' => $user->id,
                'incident_id' => $incident->id,
            ]);
            return;
        }

        // Send database + email notification
        $user->notify(new IncidentAssignedNotification($incident));

        // Send push notification
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
     * Sends to: Escalated user and target department HOD.
     */
    public function notifyIncidentEscalated(Incident $incident, Escalation $escalation): void
    {
        $recipients = collect();

        // 1. Notify escalated user
        $escalatedUser = User::find($escalation->escalated_to);
        if ($escalatedUser) {
            $recipients->push($escalatedUser);
            $escalatedUser->notify(new IncidentEscalatedNotification($incident, $escalation));
        }

        // 2. Notify target department HOD
        $toDepartment = $escalation->toDepartment;
        $hod = $toDepartment ? $toDepartment->getHeadOfDepartment() : null;
        if ($hod && $hod->id !== ($escalation->escalated_to ?? 0)) {
            $recipients->push($hod);
            $hod->notify(new IncidentEscalatedNotification($incident, $escalation));
        }

        // 3. Queue push notifications
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
    // ESCALATION RESPONSE
    // ==========================================
    
    /**
     * Notify when an escalation is responded to (accept/reject/return).
     * Sends to: The person who escalated the incident (escalated_by) and the reporter (if different).
     */
    public function notifyEscalationResponded(Incident $incident, Escalation $escalation, string $responseType, string $responseNote = ''): void
    {
        $recipients = collect();
        $responderId = auth()->id();
    
        // 1. Notify the person who escalated (escalated_by)
        $escalatedByUser = User::find($escalation->escalated_by);
        if ($escalatedByUser && $escalatedByUser->id !== $responderId) {
            $recipients->push($escalatedByUser);
            $escalatedByUser->notify(new EscalationRespondedNotification(
                $incident,
                $escalation,
                $responseType,
                $responseNote
            ));
        }
    
        // 2. Notify the reporter (if different from escalated_by and responder)
        if ($incident->reporter && 
            $incident->reporter->id !== ($escalatedByUser?->id ?? 0) && 
            $incident->reporter->id !== $responderId) {
            
            $recipients->push($incident->reporter);
            $incident->reporter->notify(new EscalationRespondedNotification(
                $incident,
                $escalation,
                $responseType,
                $responseNote
            ));
        }
    
        // 3. For accept, also notify the new assignee (if not already notified)
        if ($responseType === 'accept') {
            $assignee = User::find($incident->assigned_to);
            if ($assignee && 
                $assignee->id !== ($escalatedByUser?->id ?? 0) && 
                $assignee->id !== $responderId &&
                $assignee->id !== ($incident->reporter?->id ?? 0)) {
                
                $recipients->push($assignee);
                $assignee->notify(new EscalationRespondedNotification(
                    $incident,
                    $escalation,
                    $responseType,
                    $responseNote
                ));
            }
        }
    
        // 4. Queue push notifications
        $responseText = match ($responseType) {
            'accept' => 'accepted',
            'reject' => 'rejected',
            'return' => 'returned',
            default => 'responded to',
        };
    
        $responderName = User::find($responderId)?->name ?? 'System';
    
        $this->queuePushNotifications($recipients->unique('id'), [
            'title' => "📋 Escalation {$responseText} – #" . $incident->incident_id,
            'body' => "{$responderName} has {$responseText} the escalation for incident #{$incident->incident_id}." . 
                      ($responseNote ? " Note: {$responseNote}" : ''),
            'data' => [
                'type' => 'escalation_responded',
                'incident_id' => (string) $incident->id,
                'response_type' => $responseType,
                'click_action' => route('incidents.show', $incident->id),
            ],
        ]);
    
        Log::info('Escalation response notification sent', [
            'incident_id' => $incident->id,
            'escalation_id' => $escalation->id,
            'response_type' => $responseType,
            'responder_id' => $responderId,
            'recipients' => $recipients->pluck('id')->toArray(),
        ]);
    }

    // ==========================================
    // INCIDENT RESOLVED
    // ==========================================

    /**
     * Notify when incident is resolved.
     * Sends to: Reporter, Assigned User, HOD.
     */
    public function notifyIncidentResolved(Incident $incident): void
    {
        $recipients = collect();
        $actorId = auth()->id();

        // 1. Notify reporter
        if ($incident->reporter && $incident->reporter->id !== $actorId) {
            $recipients->push($incident->reporter);
            $incident->reporter->notify(new IncidentResolvedNotification($incident));
        }

        // 2. Notify assigned user (if different from reporter)
        if ($incident->assignedTo && $incident->assignedTo->id !== ($incident->reporter->id ?? 0)) {
            $recipients->push($incident->assignedTo);
            $incident->assignedTo->notify(new IncidentResolvedNotification($incident));
        }

        // 3. Notify HOD (if different from others)
        $department = $incident->department;
        $hod = $department ? $department->getHeadOfDepartment() : null;
        if ($hod && $hod->id !== $actorId && $hod->id !== ($incident->reporter->id ?? 0)) {
            $recipients->push($hod);
            $hod->notify(new IncidentResolvedNotification($incident));
        }

        // 4. Queue push notifications
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
     * Sends to: Reporter, Assigned User, Escalated User.
     */
    public function notifyIncidentClosed(Incident $incident): void
    {
        $recipients = collect([$incident->reporter, $incident->assignedTo, $incident->escalatedTo])
            ->filter()
            ->unique('id');

        foreach ($recipients as $user) {
            if ($user && $user->id !== auth()->id()) {
                $user->notify(new IncidentClosedNotification($incident));
            }
        }

        // Queue push notifications
        $this->queuePushNotifications($recipients, [
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
     * Sends to: Assigned User, HOD, Reporter.
     */
    public function notifyIncidentReopened(Incident $incident): void
    {
        $actorId = auth()->id();
        $title = '🔄 Incident Reopened: #' . $incident->incident_id;
        $body = "{$incident->title} has been reopened.";
        $data = [
            'type' => 'incident_reopened',
            'incident_id' => (string) $incident->id,
            'click_action' => route('incidents.show', $incident->id),
        ];

        // 1. Notify assigned user
        if ($incident->assignedTo && $incident->assignedTo->id !== $actorId) {
            $incident->assignedTo->notify(new IncidentReopenedNotification($incident));
            $this->queuePushNotification($incident->assignedTo, [
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        // 2. Notify HOD
        $department = $incident->department;
        $hod = $department ? $department->getHeadOfDepartment() : null;
        if ($hod && $hod->id !== $actorId && $hod->id !== ($incident->assignedTo->id ?? 0)) {
            $hod->notify(new IncidentReopenedNotification($incident));
            $this->queuePushNotification($hod, [
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        // 3. Notify reporter
        if ($incident->reporter && $incident->reporter->id !== $actorId) {
            $incident->reporter->notify(new IncidentReopenedNotification($incident));
            $this->queuePushNotification($incident->reporter, [
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }
    }

    // ==========================================
    // INCIDENT REJECTED
    // ==========================================

    /**
     * Notify when incident is rejected.
     * Sends to: Reporter only.
     */
    public function notifyIncidentRejected(Incident $incident): void
    {
        if (!$incident->reporter || $incident->reporter->id === auth()->id()) {
            return;
        }

        // Send notification to reporter
        $incident->reporter->notify(new IncidentRejectedNotification($incident));

        // Send push notification
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

    // ==========================================
    // COMMENTS
    // ==========================================

    /**
     * Notify about new comment on incident.
     * Sends to: Previous commenters, Assigned User, Reporter.
     */
    public function notifyNewComment(Incident $incident, IncidentComment $comment): void
    {
        $actorId = $comment->user_id;

        // 1. Notify previous commenters (excluding current)
        $previousCommenters = $incident->allComments()
            ->pluck('user_id')
            ->unique()
            ->filter(function ($userId) use ($actorId) {
                return $userId !== $actorId;
            });

        foreach ($previousCommenters as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->notify(new NewCommentNotification($incident, $comment));
            }
        }

        // 2. Notify assigned user and reporter (if not already notified)
        $pushRecipients = collect([$incident->assignedTo, $incident->reporter])
            ->filter(function ($user) use ($actorId) {
                return $user && $user->id !== $actorId;
            })
            ->unique('id');

        // Send notifications to assigned/reporter if they haven't been notified
        foreach ($pushRecipients as $user) {
            if (!$previousCommenters->contains($user->id)) {
                $user->notify(new NewCommentNotification($incident, $comment));
            }
        }

        // 3. Queue push notifications
        $commenterName = $comment->user ? $comment->user->name : 'System';
        $this->queuePushNotifications($pushRecipients, [
            'title' => '💬 New Comment on Incident',
            'body' => "{$commenterName}: " . \Str::limit($comment->content, 100),
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
        $actorId = $comment->user_id;

        foreach ($comment->mentioned_users as $user) {
            if ($user->id === $actorId) {
                continue; // Don't mention yourself
            }

            // Send notification
            $user->notify(new MentionNotification($incident, $comment));

            // Send push notification
            $commenterName = $comment->user ? $comment->user->name : 'System';
            $this->queuePushNotification($user, [
                'title' => '👋 You Were Mentioned',
                'body' => "{$commenterName} mentioned you in incident #{$incident->incident_id}",
                'data' => [
                    'type' => 'mentioned',
                    'incident_id' => (string) $incident->id,
                    'comment_id' => (string) $comment->id,
                    'click_action' => route('incidents.show', $incident->id),
                ],
            ]);
        }
    }

    // ==========================================
    // SLA BREACH & AUTO-ESCALATION
    // ==========================================

    /**
     * Send SLA breach notification.
     * Sends to: Assigned User, HOD.
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

        // 1. Notify assigned user
        if ($incident->assignedTo) {
            $incident->assignedTo->notify(new NewIncidentNotification($incident));
            $this->queuePushNotification($incident->assignedTo, [
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        // 2. Notify HOD
        $department = $incident->department;
        $hod = $department ? $department->getHeadOfDepartment() : null;
        if ($hod && $hod->id !== ($incident->assignedTo->id ?? 0)) {
            $hod->notify(new NewIncidentNotification($incident));
            $this->queuePushNotification($hod, [
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }
    }

    /**
     * Check SLA breach and auto-escalate based on escalation matrix.
     * This is triggered by the cron job.
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
            $notificationData['title'] ?? 'SAMANVAY Notification',
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
        if ($users->isEmpty()) {
            return;
        }

        $tokens = $users->pluck('fcm_token')->filter()->values()->toArray();

        if (!empty($tokens)) {
            SendBulkPushNotificationJob::dispatch(
                $tokens,
                $notificationData['title'] ?? 'SAMANVAY Notification',
                $notificationData['body'] ?? 'You have a new notification',
                $notificationData['data'] ?? []
            )->onQueue('notifications');

            Log::debug('Bulk push notifications queued', [
                'token_count' => count($tokens),
                'title' => $notificationData['title'] ?? 'N/A',
            ]);
        }
    }

    /**
     * Send notification to multiple users (helper).
     */
    protected function sendToUsers($users, $notification): void
    {
        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            if ($user instanceof User) {
                $user->notify($notification);
            }
        }
    }
}