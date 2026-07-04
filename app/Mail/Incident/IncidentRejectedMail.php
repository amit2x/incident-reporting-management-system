<?php

namespace App\Mail\Incident;

use App\Mail\BaseMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class IncidentRejectedMail extends BaseMail
{
    protected Incident $incident;

    protected User $actor;

    public function __construct(Incident $incident, User $actor)
    {
        $this->incident = $incident;
        $this->actor = $actor;

        $this->statusColor = '#DC2626';
        $this->statusLabel = 'Incident Rejected';
        $this->statusType = 'rejected';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectPrefix.' Incident Rejected – '.$this->incident->incident_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident.rejected',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'greeting' => 'Hello '.($this->incident->reporter?->name ?? 'User').',',
                'body' => 'The incident you reported has been rejected. Please review the reason and take necessary action.',
                'actionUrl' => route('incidents.show', $this->incident),
                'extraFields' => [
                    'Rejected By' => $this->actor->name,
                    'Rejection Reason' => $this->incident->rejection_reason ?? 'N/A',
                ],
            ])
        );
    }
}
