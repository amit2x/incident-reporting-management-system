<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentAssignedMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentAssignedNotification extends Notification implements ShouldQueue
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
    // public function toMail(object $notifiable): MailMessage
    // {
    //     $url = route('incidents.show', $this->incident->id);

    //     return (new MailMessage)
    //         ->subject("📋 Incident Assigned: #{$this->incident->incident_id}")
    //         ->greeting("Hello {$notifiable->name},")
    //         ->line('An incident has been assigned to you.')
    //         ->line("**Incident ID:** {$this->incident->incident_id}")
    //         ->line("**Title:** {$this->incident->title}")
    //         ->line('**Priority:** '.ucfirst($this->incident->priority))
    //         ->line('**Severity:** '.ucfirst($this->incident->severity))
    //         ->line("**Department:** {$this->incident->department->name}")
    //         ->line('**Location:** '.($this->incident->location ?? 'Not specified'))
    //         ->action('View Incident Details', $url)
    //         ->line('Please acknowledge and take appropriate action.')
    //         // ->salutation('Regards,<br>IRMS Notification System');
    //         ->salutation("Regards,\nSAMANVAY");
    // }

    public function toMail(object $notifiable)
    {
        // Get actor from the person who last modified the incident (assigned it)
        $actor = $this->getActor();

        return (new IncidentAssignedMail($this->incident, $actor, $notifiable))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_assigned',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'assigned_by' => $this->getActor()?->name ?? 'System',
            'message' => "Incident #{$this->incident->incident_id} has been assigned to you",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    /**
     * Get the actor who performed the action.
     */
    protected function getActor(): ?User
    {
        // Check if we're in a web context and have an authenticated user
        if (auth()->check()) {
            return auth()->user();
        }

        // Check if incident has an assignment log with actor
        $lastAssignment = $this->incident->assignments()->latest()->first();
        if ($lastAssignment && $lastAssignment->assigned_by) {
            return User::find($lastAssignment->assigned_by);
        }

        // Fallback: check if the incident was modified by someone
        if ($this->incident->updated_by) {
            return User::find($this->incident->updated_by);
        }

        return null;
    }
}
