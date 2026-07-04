<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentRejectedMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;

    public function __construct(Incident $incident)
    {
        $this->incident = $incident;
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    // public function toMail(object $notifiable): MailMessage
    // {
    //     $url = route('incidents.show', $this->incident->id);

    //     return (new MailMessage)
    //         ->subject("❌ Incident Rejected: #{$this->incident->incident_id}")
    //         ->greeting("Hello {$notifiable->name},")
    //         ->line("Your incident has been rejected.")
    //         ->line("**Incident ID:** {$this->incident->incident_id}")
    //         ->line("**Title:** {$this->incident->title}")
    //         ->line("**Reason:** {$this->incident->rejection_reason}")
    //         ->action('View Incident', $url)
    //         ->line('Please review the rejection reason and resubmit if necessary.')
    //         // ->salutation('Regards,<br>IRMS Notification System');
    //                     ->salutation("Regards,\nSAMANVAY");

    // }

    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();

        return (new IncidentRejectedMail($this->incident, $actor))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_rejected',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'message' => "Your incident #{$this->incident->incident_id} has been rejected. Reason: {$this->incident->rejection_reason}",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    protected function getActor(): ?User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        if ($this->incident->rejected_by) {
            return User::find($this->incident->rejected_by);
        }

        return null;
    }
}
