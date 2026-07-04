<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentMentionMail;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;

    protected IncidentComment $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Incident $incident, IncidentComment $comment)
    {
        $this->incident = $incident;
        $this->comment = $comment;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     $url = route('incidents.show', $this->incident->id);

    //     return (new MailMessage)
    //         ->subject("👋 You were mentioned in Incident #{$this->incident->incident_id}")
    //         ->greeting("Hello {$notifiable->name},")
    //         ->line("**{$this->comment->user->name}** mentioned you in a comment on incident #{$this->incident->incident_id}:")
    //         ->line("\"{$this->comment->content}\"")
    //         ->action('View Incident', $url)
    //         // ->salutation('Regards,<br>IRMS Notification System');
    //                     ->salutation("Regards,\nSAMANVAY");

    // }

    public function toMail(object $notifiable)
    {
        $actor = $this->getActor();

        return (new IncidentMentionMail($this->incident, $actor, $this->comment))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mentioned',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_id,
            'comment_id' => $this->comment->id,
            'mentioned_by' => $this->getActor()?->name ?? 'System',
            'comment_preview' => \Str::limit($this->comment->content, 100),
            'mentioner_name' => $this->comment->user->name,
            'message' => "{$this->comment->user->name} mentioned you in a comment for incident #{$this->incident->incident_id}.",
            'url' => route('incidents.show', $this->incident->id),
        ];
    }

    protected function getActor(): ?User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        // Get the comment author
        if ($this->comment->user_id) {
            return User::find($this->comment->user_id);
        }

        return null;
    }
}
