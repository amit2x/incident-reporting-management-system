<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewIncidentNotification extends Notification implements ShouldQueue
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
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('incidents.show', $this->incident->id);

        return (new MailMessage)
            ->subject("🚨 New Incident Reported: #{$this->incident->incident_id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new incident has been reported that requires your attention.")
            ->line("**Incident ID:** {$this->incident->incident_id}")
            ->line("**Title:** {$this->incident->title}")
            ->line("**Severity:** " . ucfirst($this->incident->severity))
            ->line("**Priority:** " . ucfirst($this->incident->priority))
            ->line("**Department:** {$this->incident->department->name}")
            ->line("**Category:** {$this->incident->category->name}")
            ->line("**Reported By:** " . ($this->incident->is_anonymous ? 'Anonymous' : $this->incident->reporter->name))
            ->line("**Location:** " . ($this->incident->location ?? 'Not specified'))
            ->when($this->incident->sla_due_at, function ($mail) {
                return $mail->line("**SLA Due:** {$this->incident->sla_due_at->format('d M Y, H:i')}");
            })
            ->action('View Incident', $url)
            ->line('Please take appropriate action at your earliest convenience.')
            ->salutation('Regards,<br>IRMS Notification System');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_incident',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'severity' => $this->incident->severity,
            'department' => $this->incident->department->name,
            'message' => "New incident #{$this->incident->incident_id} reported: {$this->incident->title}",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }
}
