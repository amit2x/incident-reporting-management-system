<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentCreatedMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class IncidentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;

    /**
     * Create a new notification instance.
     */
    public function __construct(Incident $incident)
    {
        $this->incident = $incident;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();
        
        // If actor is null, use the notifiable user or find a fallback
        if (!$actor) {
            $actor = $this->incident->reporter;
        }
        
        // If still null, use the notifiable user if it's a User instance
        if (!$actor && $notifiable instanceof User) {
            $actor = $notifiable;
        }
        
        // Ultimate fallback: find the first admin or system user
        if (!$actor) {
            $actor = User::role('admin')->first() ?? User::first();
        }
        
        // If still null, create a temporary system user (should never happen)
        if (!$actor) {
            Log::warning('No actor found for created notification', [
                'incident_id' => $this->incident->id,
            ]);
            
            $actor = User::firstOrCreate(
                ['email' => 'system@' . parse_url(config('app.url'), PHP_URL_HOST)],
                [
                    'name' => 'System',
                    'username' => 'system',
                    'password' => bcrypt(str_random(32)),
                ]
            );
        }

        return (new IncidentCreatedMail($this->incident, $actor))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $actor = $this->getActor();
        
        return [
            'type' => 'incident_created',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'severity' => $this->incident->severity,
            'priority' => $this->incident->priority,
            'status' => $this->incident->status,
            'department' => $this->incident->department?->name ?? 'N/A',
            'category' => $this->incident->category?->name ?? 'N/A',
            'reported_by' => $this->incident->reporter?->name ?? 'Anonymous',
            'reported_by_id' => $this->incident->reported_by,
            'is_anonymous' => $this->incident->is_anonymous,
            'message' => "New incident #{$this->incident->incident_id} has been reported",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    /**
     * Get the actor who created the incident.
     */
    protected function getActor(): ?User
    {
        // Strategy 1: Check authenticated user
        if (auth()->check()) {
            return auth()->user();
        }

        // Strategy 2: Check reporter
        if ($this->incident->reported_by) {
            return User::find($this->incident->reported_by);
        }

        // Strategy 3: Check if incident has created in status_actions
        $statusAction = $this->incident->statusActions()
            ->where('action', 'created')
            ->latest('acted_at')
            ->first();
            
        if ($statusAction && $statusAction->user_id) {
            return User::find($statusAction->user_id);
        }

        return null;
    }
}