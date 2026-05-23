<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentAssignedNotification extends Notification
{
    use Queueable;

    protected $incident;

    public function __construct(Incident $incident)
    {
        $this->incident = $incident;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('incidents.show', $this->incident->id);

        return (new MailMessage)
            ->subject("📋 Incident Assigned: #{$this->incident->incident_id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("An incident has been assigned to you.")
            ->line("**Incident ID:** {$this->incident->incident_id}")
            ->line("**Title:** {$this->incident->title}")
            ->line("**Priority:** " . ucfirst($this->incident->priority))
            ->line("**Department:** {$this->incident->department->name}")
            ->line("**Assigned By:** " . (auth()->user()->name ?? 'System'))
            ->action('View Details', $url);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'incident_assigned',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'message' => "Incident #{$this->incident->incident_id} has been assigned to you",
            'assigned_by' => auth()->user()->name ?? 'System',
            'url' => route('incidents.show', $this->incident->id),
        ];
    }
}