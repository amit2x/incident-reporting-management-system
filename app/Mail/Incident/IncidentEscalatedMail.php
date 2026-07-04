<?php

namespace App\Mail\Incident;

use App\Mail\BaseMail;
use App\Models\Escalation;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class IncidentEscalatedMail extends BaseMail
{
    protected Incident $incident;

    protected User $actor;

    protected Escalation $escalation;

    public function __construct(Incident $incident, User $actor, Escalation $escalation)
    {
        $this->incident = $incident;
        $this->actor = $actor;
        $this->escalation = $escalation;

        $this->statusColor = '#FF8F00';
        $this->statusLabel = 'Incident Escalated';
        $this->statusType = 'escalated';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectPrefix.' Incident Escalated – Immediate Attention Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident.escalated',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'escalation' => $this->escalation,
                'greeting' => 'Hello '.($this->actor->name).',',
                'body' => 'An incident has been escalated to you. Immediate attention is required.',
                'actionUrl' => route('incidents.show', $this->incident),
                'extraFields' => [
                    'Escalation Level' => $this->escalation->level ?? 'N/A',
                    'Escalated To' => $this->escalation->escalatedTo?->name ?? 'N/A',
                    'Reason' => $this->escalation->reason ?? 'N/A',
                ],
            ])
        );
    }
}
