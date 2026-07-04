<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $channels = ['mail', 'database'];

    public function via($notifiable): array
    {
        return $this->channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => $this->getDatabaseMessage(),
            'url' => $this->getDatabaseUrl(),
            'icon' => $this->getDatabaseIcon(),
            'type' => $this->getDatabaseType(),
        ];
    }

    abstract protected function getDatabaseMessage(): string;
    abstract protected function getDatabaseUrl(): string;
    abstract protected function getDatabaseIcon(): string;
    abstract protected function getDatabaseType(): string;
}