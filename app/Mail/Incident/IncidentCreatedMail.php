<?php

namespace App\Mail\Incident;

use App\Mail\BaseMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class IncidentCreatedMail extends BaseMail
{
    protected Incident $incident;

    protected ?User $actor;

    public function __construct(Incident $incident, ?User $actor = null)
    {
        $this->incident = $incident;
        $this->actor = $actor ?? $incident->reporter;

        $this->statusColor = '#0D47A1';
        $this->statusLabel = 'New Incident Reported';
        $this->statusType = 'created';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectPrefix.' New Incident Reported – '.$this->incident->incident_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident.created',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'greeting' => 'Hello Team,',
                'body' => 'A new incident has been reported. Please review and take appropriate action.',
                'actionUrl' => route('incidents.show', $this->incident),
                'extraFields' => [
                    'Reported By' => $this->incident->reporter?->name ?? 'N/A',
                    'Status' => ucfirst($this->incident->status),
                ],
            ])
        );
    }
}
