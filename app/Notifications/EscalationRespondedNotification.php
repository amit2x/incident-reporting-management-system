<?php

namespace App\Notifications;

use App\Mail\Incident\EscalationRespondedMail;
use App\Models\Escalation;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EscalationRespondedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;
    protected Escalation $escalation;
    protected string $responseType;
    protected string $responseNote;

    public function __construct(Incident $incident, Escalation $escalation, string $responseType, string $responseNote = '')
    {
        $this->incident = $incident;
        $this->escalation = $escalation;
        $this->responseType = $responseType;
        $this->responseNote = $responseNote;
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable)
    {
        // Get the responder (the user who responded to escalation)
        $responder = User::find($this->escalation->responded_by);
        
        // Get the escalated by user (who originally escalated)
        $escalatedBy = User::find($this->escalation->escalated_by);

        // Get recipient name
        $recipientName = $notifiable instanceof User ? $notifiable->name : 'User';

        $mail = (new EscalationRespondedMail(
            $this->incident,
            $escalatedBy,
            $this->escalation,
            $this->responseType,
            $this->responseNote,
            $responder
        ))->setRecipientName($recipientName);

        return $mail->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        $responder = User::find($this->escalation->responded_by);
        $escalatedBy = User::find($this->escalation->escalated_by);

        $responseMessages = [
            'accept' => 'accepted',
            'reject' => 'rejected',
            'return' => 'returned',
        ];

        $responseText = $responseMessages[$this->responseType] ?? $this->responseType;

        return [
            'type' => 'escalation_responded',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'response_type' => $this->responseType,
            'responder' => $responder?->name ?? 'System',
            'responder_id' => $this->escalation->responded_by,
            'escalated_by' => $escalatedBy?->name ?? 'System',
            'escalation_level' => $this->escalation->level,
            // 'message' => "Escalation was {$responseText} by {$responder?->name ?? 'System'} on incident #{$this->incident->incident_id}",
            'message' => "Escalation was {$responseText} by {$responder?->name} on incident #{$this->incident->incident_id}",
            'response_note' => $this->responseNote,
            'url' => route('incidents.show', $this->incident->id),
        ];
    }
}