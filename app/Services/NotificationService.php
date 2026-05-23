<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\User;
use App\Models\IncidentComment;
use App\Models\Escalation;
use App\Notifications\NewIncidentNotification;
use App\Notifications\IncidentAssignedNotification;
use App\Notifications\IncidentEscalatedNotification;
use App\Notifications\IncidentResolvedNotification;
use App\Notifications\IncidentClosedNotification;
use App\Notifications\NewCommentNotification;
use App\Notifications\MentionNotification;
use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendBulkPushNotificationJob;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Notify about new incident
     */
    public function notifyNewIncident(Incident $incident): void
    {
        $recipients = collect();

        // Get HOD
        $hod = $incident->department->getHeadOfDepartment();
        if ($hod) {
            $recipients->push($hod);
            Notification::send($hod, new NewIncidentNotification($incident));
        }

        // Get supervisors
        $supervisors = $incident->department->getSupervisors();
        foreach ($supervisors as $supervisor) {
            if ($supervisor->id !== $hod?->id) {
                $recipients->push($supervisor);
                Notification::send($supervisor, new NewIncidentNotification($incident));
            }
        }

        // Notify admins for critical incidents
        if (in_array($incident->severity, ['critical', 'high'])) {
            $admins = User::role(['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                $recipients->push($admin);
                Notification::send($admin, new NewIncidentNotification($incident));
            }
        }

        // Queue push notifications
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

    /**
     * Notify about incident assignment
     */
    public function notifyIncidentAssigned(Incident $incident, int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            // Send email + database notification
            Notification::send($user, new IncidentAssignedNotification($incident));

            // Queue push notification
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
    }

    /**
     * Notify about incident escalation
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
        if ($hod && $hod->id !== $escalation->escalated_to) {
            $recipients->push($hod);
            Notification::send($hod, new IncidentEscalatedNotification($incident, $escalation));
        }

        // Queue push notifications
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

    /**
     * Notify about incident resolution
     */
    public function notifyIncidentResolved(Incident $incident): void
    {
        $recipients = collect();

        // Notify reporter
        $reporter = $incident->reporter;
        if ($reporter) {
            $recipients->push($reporter);
            Notification::send($reporter, new IncidentResolvedNotification($incident));
        }

        // Notify HOD
        $hod = $incident->department->getHeadOfDepartment();
        if ($hod && $hod->id !== $reporter->id) {
            $recipients->push($hod);
            Notification::send($hod, new IncidentResolvedNotification($incident));
        }

        // Queue push notifications
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

    /**
     * Notify about incident closure
     */
    public function notifyIncidentClosed(Incident $incident): void
    {
        $users = collect([
            $incident->reporter,
            $incident->assignedTo,
            $incident->escalatedTo,
        ])->filter()->unique('id');

        foreach ($users as $user) {
            Notification::send($user, new IncidentClosedNotification($incident));
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

    /**
     * Notify about new comment
     */
    public function notifyNewComment(Incident $incident, IncidentComment $comment): void
    {
        // Get unique commenters
        $commenters = $incident->allComments()
            ->pluck('user_id')
            ->unique()
            ->filter(function ($userId) use ($comment) {
                return $userId !== $comment->user_id;
            });

        foreach ($commenters as $userId) {
            $user = User::find($userId);
            if ($user) {
                Notification::send($user, new NewCommentNotification($incident, $comment));
            }
        }

        // Notify assigned user and reporter via push
        $pushRecipients = collect([
            $incident->assignedTo,
            $incident->reporter,
        ])->filter(function ($user) use ($comment) {
            return $user && $user->id !== $comment->user_id;
        })->unique('id');

        $this->queuePushNotifications($pushRecipients, [
            'title' => '💬 New Comment on Incident',
            'body' => "{$comment->user->name}: {$comment->content}",
            'data' => [
                'type' => 'new_comment',
                'incident_id' => (string) $incident->id,
                'comment_id' => (string) $comment->id,
                'click_action' => route('incidents.show', $incident->id),
            ],
        ]);
    }

    /**
     * Notify mentioned users
     */
    public function notifyMentionedUsers(Incident $incident, IncidentComment $comment): void
    {
        foreach ($comment->mentioned_users as $user) {
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

    /**
     * Queue push notification for single user
     */
    protected function queuePushNotification(User $user, array $notificationData): void
    {
        if ($user->fcm_token) {
            SendPushNotificationJob::dispatch(
                $user,
                $notificationData['title'],
                $notificationData['body'],
                $notificationData['data'] ?? []
            )->onQueue('notifications');
        }
    }

    /**
     * Queue push notifications for multiple users
     */
    protected function queuePushNotifications($users, array $notificationData): void
    {
        $tokens = $users->pluck('fcm_token')->filter()->values()->toArray();
        
        if (!empty($tokens)) {
            SendBulkPushNotificationJob::dispatch(
                $tokens,
                $notificationData['title'],
                $notificationData['body'],
                $notificationData['data'] ?? []
            )->onQueue('notifications');
        }
    }
}