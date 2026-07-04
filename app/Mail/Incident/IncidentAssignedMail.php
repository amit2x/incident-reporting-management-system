<?php

namespace App\Mail\Incident;

use App\Mail\BaseMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class IncidentAssignedMail extends BaseMail
{
    protected Incident $incident;

    protected User $actor;

    protected ?User $assignee;

    public function __construct(Incident $incident, User $actor, ?User $assignee = null)
    {
        $this->incident = $incident;
        $this->actor = $actor;
        $this->assignee = $assignee;

        $this->statusColor = '#0D47A1';
        $this->statusLabel = 'Incident Assigned';
        $this->statusType = 'assigned';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectPrefix.' Incident Assigned – '.$this->incident->incident_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident.assigned',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'assignee' => $this->assignee,
                'greeting' => 'Hello '.($this->assignee?->name ?? 'User').',',
                'body' => 'An incident has been assigned to you. Please review the details and take appropriate action.',
                'actionUrl' => route('incidents.show', $this->incident),
            ])
        );
    }
}
