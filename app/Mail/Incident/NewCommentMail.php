<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;
use App\Models\IncidentComment;

class NewCommentMail extends BaseIncidentMail
{
    protected string $notificationType = 'comment';
    protected IncidentComment $comment;

    public function __construct(Incident $incident, User $actor, IncidentComment $comment)
    {
        parent::__construct($incident, $actor);
        $this->comment = $comment;
        
        $this->extraFields = [
            'Comment' => $comment->content,
            'Comment By' => $actor->name,
        ];
    }

    protected function getSubject(): string
    {
        return 'New Comment Added – ' . $this->incident->incident_id;
    }

    protected function getView(): string
    {
        return 'emails.comments.new-comment';
    }

    protected function getGreeting(): string
    {
        return 'Hello <span style="color: #0d47a1;">' . 
               ($this->actor?->name ?? 'User') . 
               '</span>,';
    }

    protected function getBody(): string
    {
        return 'A new comment has been added to an incident you are following.';
    }
}