<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;
use App\Models\IncidentComment;

class MentionMail extends BaseIncidentMail
{
    protected string $notificationType = 'mention';
    protected IncidentComment $comment;

    public function __construct(Incident $incident, User $actor, IncidentComment $comment)
    {
        parent::__construct($incident, $actor);
        $this->comment = $comment;
        
        $this->extraFields = [
            'Comment' => $comment->content,
            'Mentioned By' => $actor->name,
        ];
    }

    protected function getSubject(): string
    {
        return 'You were mentioned in an Incident – ' . $this->incident->incident_id;
    }

    protected function getView(): string
    {
        return 'emails.comments.mention';
    }

    protected function getGreeting(): string
    {
        return 'Hello <span style="color: #0d47a1;">' . 
               ($this->actor?->name ?? 'User') . 
               '</span>,';
    }

    protected function getBody(): string
    {
        return 'You were mentioned in a comment on an incident.';
    }
}