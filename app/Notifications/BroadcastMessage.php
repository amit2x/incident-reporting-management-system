<?php

// app/Notifications/BroadcastMessage.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BroadcastMessage extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public int $senderId,
        public string $senderName,
        public int $airportId,
        public array $channels = ['database'],
    ) {}

    public function via($notifiable): array
    {
        return $this->channels;
    }

    /**
     * Payload consumed by NotificationController@latest / index view.
     * Matches the shape used by existing notification types.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'broadcast',
            'title' => $this->title,
            'message' => $this->message,
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'airport_id' => $this->airportId,
            'url' => route('notifications.index'),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message)
            ->salutation("— {$this->senderName}");
    }

    /**
     * FCM payload — matches what your existing FCM channel expects.
     * If your FCM channel uses a different method name, adjust accordingly.
     */
    public function toFcm($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'data' => [
                'type' => 'broadcast',
                'sender_id' => $this->senderId,
                'airport_id' => $this->airportId,
            ],
        ];
    }
}
