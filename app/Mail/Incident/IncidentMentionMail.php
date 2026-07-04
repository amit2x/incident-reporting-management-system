<?php

namespace App\Mail\Incident;

use App\Mail\BaseMail;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class IncidentMentionMail extends BaseMail
{
    protected Incident $incident;

    protected User $actor;

    protected IncidentComment $comment;

    public function __construct(Incident $incident, User $actor, IncidentComment $comment)
    {
        $this->incident = $incident;
        $this->actor = $actor;
        $this->comment = $comment;

        $this->statusColor = '#7C3AED';
        $this->statusLabel = 'You were mentioned';
        $this->statusType = 'mention';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectPrefix.' You were mentioned in an Incident – '.$this->incident->incident_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident.mention',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'comment' => $this->comment,
                'greeting' => 'Hello '.($this->actor->name).',',
                'body' => 'You were mentioned in a comment on an incident.',
                'actionUrl' => route('incidents.show', $this->incident).'#comment-'.$this->comment->id,
                'extraFields' => [
                    'Comment' => $this->comment->content,
                    'Mentioned By' => $this->actor->name,
                ],
            ])
        );
    }
}
