<?php

namespace App\Notifications;

use App\Models\Incident;
use App\Models\IncidentComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $incident;
    protected $comment;

    public function __construct(Incident $incident, IncidentComment $comment)
    {
        $this->incident = $incident;
        $this->comment = $comment;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('incidents.show', $this->incident->id) . '#comment-' . $this->comment->id;

        return (new MailMessage)
            ->subject("💬 New Comment on Incident #{$this->incident->incident_id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->comment->user->name}** commented on incident #{$this->incident->incident_id}")
            ->line("\"{$this->comment->content}\"")
            ->action('View Comment', $url);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_comment',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'comment_id' => $this->comment->id,
            'commenter_name' => $this->comment->user->name,
            'message' => "{$this->comment->user->name} commented: {$this->comment->content}",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }
}