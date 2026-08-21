<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentEscalatedMail;
use App\Models\Escalation;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class IncidentEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;
    protected Escalation $escalation;

    public function __construct(Incident $incident, Escalation $escalation)
    {
        $this->incident = $incident;
        $this->escalation = $escalation;
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


    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();
        
        if (!$actor) {
            $actor = auth()->user() ?? User::find(1);
        }

        // Get recipient name
        $recipientName = $notifiable instanceof User ? $notifiable->name : 'User';

        // Create mail with personalized greeting
        $mail = (new IncidentEscalatedMail($this->incident, $actor, $this->escalation))
            ->setRecipientName($recipientName)
            ->to($notifiable->email);
        
        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $actor = $this->getActor();
        
        return [
            'type' => 'incident_escalated',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'level' => $this->escalation->level,
            'escalated_by' => $actor?->name ?? 'System',
            'message' => "Incident #{$this->incident->incident_id} has been escalated to Level {$this->escalation->level}.",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    protected function getActor(): ?User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        if ($this->escalation->escalated_by) {
            return User::find($this->escalation->escalated_by);
        }

        return null;
    }
}