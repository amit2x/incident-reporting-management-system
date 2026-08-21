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
    protected ?string $recipientName;

    public function __construct(Incident $incident, User $actor, ?string $recipientName = null)
    {
        $this->incident = $incident;
        $this->actor = $actor;
        $this->recipientName = $recipientName;

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
        $greeting = $this->recipientName 
            ? "Hello {$this->recipientName}," 
            : "Hello ".($this->incident->reporter?->name ?? 'User').",";

        return new Content(
            view: 'emails.incident.resolved',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'greeting' => $greeting,
                'body' => 'The incident has been resolved. Please verify the resolution.',
                'actionUrl' => route('incidents.show', $this->incident),
                'extraFields' => [
                    'Resolved By' => $this->actor->name,
                    'Resolved At' => $this->incident->resolved_at?->format('d M Y, H:i') ?? 'N/A',
                ],
            ])
        );
    }

    public function setRecipientName(string $name): self
    {
        $this->recipientName = $name;
        return $this;
    }
}