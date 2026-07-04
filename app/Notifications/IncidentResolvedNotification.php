<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentResolvedMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentResolvedNotification extends Notification implements ShouldQueue
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
    //         ->subject("✅ Incident Resolved: #{$this->incident->incident_id}")
    //         ->greeting("Hello {$notifiable->name},")
    //         ->line('An incident has been resolved.')
    //         ->line("**Incident ID:** {$this->incident->incident_id}")
    //         ->line("**Title:** {$this->incident->title}")
    //         ->line('**Resolution Notes:** '.($this->incident->resolution_notes ?? 'No notes provided'))
    //         ->action('View Incident', $url)
    //         ->line('The incident has been marked as resolved.')
    //         // ->salutation('Regards,<br>IRMS Notification System');
    //         ->salutation("Regards,\nSAMANVAY");

    // }

    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();

        return (new IncidentResolvedMail($this->incident, $actor))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_resolved',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'resolved_by' => $this->getActor()?->name ?? 'System',
            'message' => "Incident #{$this->incident->incident_id} has been resolved",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    protected function getActor(): ?User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        // Check if incident has a resolved_by field
        if ($this->incident->resolved_by) {
            return User::find($this->incident->resolved_by);
        }

        return null;
    }
}
