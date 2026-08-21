<?php

namespace App\Mail\Incident;

use App\Mail\BaseMail;
use App\Models\Escalation;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EscalationRespondedMail extends BaseMail
{
    protected Incident $incident;
    protected ?User $escalatedBy;
    protected Escalation $escalation;
    protected string $responseType;
    protected string $responseNote;
    protected ?User $responder;
    protected ?string $recipientName;

    public function __construct(
        Incident $incident,
        ?User $escalatedBy,
        Escalation $escalation,
        string $responseType,
        string $responseNote = '',
        ?User $responder = null,
        ?string $recipientName = null
    ) {
        $this->incident = $incident;
        $this->escalatedBy = $escalatedBy;
        $this->escalation = $escalation;
        $this->responseType = $responseType;
        $this->responseNote = $responseNote;
        $this->responder = $responder;
        $this->recipientName = $recipientName;

        // Set status color based on response type
        $this->statusColor = match ($responseType) {
            'accept' => '#10B981',      // Green
            'reject' => '#DC2626',      // Red
            'return' => '#F59E0B',      // Yellow
            default => '#6B7280',       // Gray
        };

        $this->statusLabel = match ($responseType) {
            'accept' => 'Escalation Accepted',
            'reject' => 'Escalation Rejected',
            'return' => 'Escalation Returned',
            default => 'Escalation Responded',
        };

        $this->statusType = 'escalation_responded';
    }

    public function envelope(): Envelope
    {
        $responseText = match ($this->responseType) {
            'accept' => 'Accepted',
            'reject' => 'Rejected',
            'return' => 'Returned',
            default => 'Responded',
        };

        return new Envelope(
            subject: $this->subjectPrefix . " Escalation {$responseText} – " . $this->incident->incident_id,
        );
    }

    public function content(): Content
    {
        $responderName = $this->responder?->name ?? 'System';
        $escalatedByName = $this->escalatedBy?->name ?? 'System';

        // Build greeting with recipient name
        $greeting = $this->recipientName 
            ? "Hello {$this->recipientName}," 
            : "Hello {$escalatedByName},";

        $responseText = match ($this->responseType) {
            'accept' => 'accepted',
            'reject' => 'rejected',
            'return' => 'returned',
            default => 'responded to',
        };

        $body = "The escalation you raised for incident #{$this->incident->incident_id} has been {$responseText} by {$responderName}.";

        if ($this->responseNote) {
            $body .= "\n\nResponder's Note: " . $this->responseNote;
        }

        // Additional info based on response type
        $extraFields = [
            'Escalation Level' => $this->escalation->level ?? 'N/A',
            'Escalated To' => User::find($this->escalation->escalated_to)?->name ?? 'N/A',
            'Response Type' => ucfirst($this->responseType),
            'Responded By' => $responderName,
            'Responded At' => $this->escalation->responded_at 
                ? \Carbon\Carbon::parse($this->escalation->responded_at)->format('d M Y, H:i') 
                : 'N/A',
        ];

        if ($this->responseNote) {
            $extraFields['Response Note'] = $this->responseNote;
        }

        // Add acceptance specific fields
        if ($this->responseType === 'accept') {
            $extraFields['New Status'] = 'In Progress';
            $extraFields['Assigned To'] = $responderName;
        } elseif ($this->responseType === 'reject') {
            $extraFields['New Status'] = 'Open';
        } elseif ($this->responseType === 'return') {
            $extraFields['New Status'] = $this->incident->status === 'escalated' ? 'Escalated (Previous Level)' : 'Open';
        }

        return new Content(
            view: 'emails.incident.escalation_responded',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'escalatedBy' => $this->escalatedBy,
                'escalation' => $this->escalation,
                'responder' => $this->responder,
                'responseType' => $this->responseType,
                'responseText' => $responseText,
                'responseNote' => $this->responseNote,
                'responderName' => $responderName,
                'greeting' => $greeting,
                'body' => $body,
                'actionUrl' => route('incidents.show', $this->incident->id),
                'extraFields' => $extraFields,
            ])
        );
    }

    public function setRecipientName(string $name): self
    {
        $this->recipientName = $name;
        return $this;
    }
}