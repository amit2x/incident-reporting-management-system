<?php

namespace App\Mail\Incident;

use App\Mail\BaseMail;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class IncidentCommentMail extends BaseMail
{
    protected Incident $incident;

    protected User $actor;

    protected IncidentComment $comment;

    public function __construct(Incident $incident, User $actor, IncidentComment $comment)
    {
        $this->incident = $incident;
        $this->actor = $actor;
        $this->comment = $comment;

        $this->statusColor = '#0891B2';
        $this->statusLabel = 'New Comment Added';
        $this->statusType = 'comment';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectPrefix.' New Comment Added – '.$this->incident->incident_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident.comment',
            with: array_merge($this->getViewData(), [
                'incident' => $this->incident,
                'actor' => $this->actor,
                'comment' => $this->comment,
                'greeting' => 'Hello '.($this->actor->name).',',
                'body' => 'A new comment has been added to an incident you are following.',
                'actionUrl' => route('incidents.show', $this->incident).'#comment-'.$this->comment->id,
                'extraFields' => [
                    'Comment' => $this->comment->content,
                    'Comment By' => $this->actor->name,
                ],
            ])
        );
    }
}
