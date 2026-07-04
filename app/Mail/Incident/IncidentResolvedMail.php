<?php

namespace App\Mail\Incident;

use App\Mail\BaseMail;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class IncidentResolvedMail extends BaseMail
{
    protected Incident $incident;

    protected User $actor;

    public function __construct(Incident $incident, User $actor)
    {
        $this->incident = $incident;
        $this->actor = $actor;

        $this->statusColor = '#2E7D32';
        $this->statusLabel = 'Incident Resolved';
        $this->statusType = 'resolved';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectPrefix.' Incident Resolved – '.$this->incident->incident_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident.resolved',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'greeting' => 'Hello '.($this->incident->reporter?->name ?? 'User').',',
                'body' => 'The incident you reported has been resolved. Please verify the resolution.',
                'actionUrl' => route('incidents.show', $this->incident),
                'extraFields' => [
                    'Resolved By' => $this->actor->name,
                    'Resolved At' => $this->incident->resolved_at?->format('d M Y, H:i') ?? 'N/A',
                ],
            ])
        );
    }
}
