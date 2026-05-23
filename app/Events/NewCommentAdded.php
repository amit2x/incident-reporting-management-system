<?php

namespace App\Events;

use App\Models\Incident;
use App\Models\IncidentComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $incident;
    public $comment;

    public function __construct(Incident $incident, IncidentComment $comment)
    {
        $this->incident = $incident;
        $this->comment = $comment->load('user');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('incident.' . $this->incident->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'content' => $this->comment->content,
            'user' => [
                'id' => $this->comment->user->id,
                'name' => $this->comment->user->name,
                'avatar' => $this->comment->user->avatar_url,
            ],
            'created_at' => $this->comment->created_at->diffForHumans(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.added';
    }
}