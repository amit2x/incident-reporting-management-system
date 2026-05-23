<?php

namespace App\Notifications;

use App\Models\Incident;
use App\Models\Escalation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $incident;
    protected $escalation;

    public function __construct(Incident $incident, Escalation $escalation)
    {
        $this->incident = $incident;
        $this->escalation = $escalation;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('incidents.show', $this->incident->id);

        return (new MailMessage)
            ->subject("⬆️ Escalated: Incident #{$this->incident->incident_id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("An incident has been escalated to you for immediate attention.")
            ->line("**Incident ID:** {$this->incident->incident_id}")
            ->line("**Title:** {$this->incident->title}")
            ->line("**Escalation Level:** {$this->escalation->level}")
            ->line("**Escalated From:** {$this->escalation->fromDepartment->name}")
            ->line("**Escalated By:** {$this->escalation->escalatedBy->name}")
            ->line("**Reason:** {$this->escalation->reason}")
            ->line("**Priority:** " . ucfirst($this->incident->priority))
            ->action('View Incident', $url)
            ->line('This escalation requires your immediate response.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'incident_escalated',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'escalation_level' => $this->escalation->level,
            'message' => "Incident #{$this->incident->incident_id} has been escalated to you",
            'reason' => $this->escalation->reason,
            'url' => route('incidents.show', $this->incident->id),
        ];
    }
}