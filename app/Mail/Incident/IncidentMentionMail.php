<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentMentionMail;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;
    protected IncidentComment $comment;

    public function __construct(Incident $incident, IncidentComment $comment)
    {
        $this->incident = $incident;
        $this->comment = $comment;
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();
        
        if (!$actor) {
            $actor = auth()->user() ?? User::find($this->comment->user_id);
        }
        
        if (!$actor) {
            $actor = User::role('admin')->first() ?? User::first();
        }
        
        if (!$actor) {
            Log::warning('No actor found for mention notification', [
                'incident_id' => $this->incident->id,
            ]);
            
            $actor = User::firstOrCreate(
                ['email' => 'system@' . parse_url(config('app.url'), PHP_URL_HOST)],
                [
                    'name' => 'System',
                    'username' => 'system',
                    'password' => bcrypt(str_random(32)),
                ]
            );
        }

        $recipientName = $notifiable instanceof User ? $notifiable->name : 'User';

        $mail = (new IncidentMentionMail($this->incident, $actor, $this->comment))
            ->setRecipientName($recipientName)
            ->to($notifiable->email);
        
        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $actor = $this->getActor();
        
        return [
            'type' => 'mention',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'comment_id' => $this->comment->id,
            'mentioned_by' => $actor?->name ?? 'System',
            'mentioned_by_id' => $actor?->id,
            'comment_preview' => \Str::limit($this->comment->content, 100),
            'message' => "{$actor?->name ?? 'Someone'} mentioned you in incident #{$this->incident->incident_id}",
            'url' => route('incidents.show', $this->incident->id) . '#comment-' . $this->comment->id,
        ];
    }

    protected function getActor(): ?User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        if ($this->comment->user_id) {
            return $this->comment->user;
        }

        return null;
    }
}