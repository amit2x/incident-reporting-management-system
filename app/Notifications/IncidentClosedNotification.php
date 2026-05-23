<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentClosedNotification extends Notification implements ShouldQueue
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
            ->subject("🔒 Incident Closed: #{$this->incident->incident_id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("An incident has been closed.")
            ->line("**Incident ID:** {$this->incident->incident_id}")
            ->line("**Title:** {$this->incident->title}")
            ->action('View Incident', $url)
            ->line('This incident is now closed.')
            ->salutation('Regards,<br>IRMS Notification System');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_closed',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'message' => "Incident #{$this->incident->incident_id} has been closed",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }
}
