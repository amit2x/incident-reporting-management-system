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
    protected ?string $recipientName;

    public function __construct(Incident $incident, ?User $actor = null, ?string $recipientName = null)
    {
        $this->incident = $incident;
        $this->actor = $actor ?? $incident->reporter;
        $this->recipientName = $recipientName;

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
        // Use recipient name if provided, otherwise use a generic greeting
        $greeting = $this->recipientName 
            ? "Hello {$this->recipientName}," 
            : 'Hello Team,';

        return new Content(
            view: 'emails.incident.created',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'greeting' => $greeting,
                'body' => 'A new incident has been reported. Please review and take appropriate action.',
                'actionUrl' => route('incidents.show', $this->incident),
                'extraFields' => [
                    'Reported By' => $this->incident->is_anonymous 
                        ? 'Anonymous' 
                        : ($this->incident->reporter?->name ?? 'N/A'),
                    'Status' => ucfirst(str_replace('_', ' ', $this->incident->status)),
                ],
            ])
        );
    }

    /**
     * Set the recipient name for personalized greeting.
     */
    public function setRecipientName(string $name): self
    {
        $this->recipientName = $name;
        return $this;
    }
}