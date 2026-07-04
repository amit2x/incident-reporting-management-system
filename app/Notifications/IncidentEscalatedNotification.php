<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentEscalatedMail;
use App\Models\Escalation;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;

    protected Escalation $escalation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Incident $incident, Escalation $escalation)
    {
        $this->incident = $incident;
        $this->escalation = $escalation;
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
    //         ->subject("⬆️ Incident Escalated: #{$this->incident->incident_id}")
    //         ->greeting("Hello {$notifiable->name},")
    //         ->line('An incident has been escalated to you for immediate attention.')
    //         ->line("**Incident ID:** {$this->incident->incident_id}")
    //         ->line("**Title:** {$this->incident->title}")
    //         ->line("**Escalation Level:** {$this->escalation->level}")
    //         ->line("**Escalated From:** {$this->escalation->fromDepartment->name}")
    //         ->line("**Escalated By:** {$this->escalation->escalatedBy->name}")
    //         ->line("**Reason:** {$this->escalation->reason}")
    //         ->line('**Priority:** '.ucfirst($this->incident->priority))
    //         ->action('View Incident', $url)
    //         ->line('This escalation requires your immediate response.')
    //         // ->salutation('Regards,<br>IRMS Notification System');
    //         ->salutation("Regards,\nSAMANVAY");

    // }

    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();

        return (new IncidentEscalatedMail($this->incident, $actor, $this->escalation))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_escalated',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'escalated_by' => $this->getActor()?->name ?? 'System',
            'escalation_level' => $this->escalation->level,
            'message' => "Incident #{$this->incident->incident_id} has been escalated to you",
            'reason' => $this->escalation->reason,
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    protected function getActor(): ?User
    {
        // Check if we're in a web context
        if (auth()->check()) {
            return auth()->user();
        }

        // Get from escalation record
        if ($this->escalation->escalated_by) {
            return User::find($this->escalation->escalated_by);
        }

        return null;
    }
}
