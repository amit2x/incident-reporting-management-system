<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

abstract class BaseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected string $subjectPrefix = '[SAMANVAY]';

    protected string $statusColor = '#0D47A1';

    protected string $statusLabel = 'Incident Update';

    protected string $statusType = 'default';

    /**
     * Get the status banner data.
     */
    protected function getStatusBanner(): array
    {
        return [
            'color' => $this->statusColor,
            'label' => $this->statusLabel,
            'type' => $this->statusType,
        ];
    }

    /**
     * Get the common view data.
     */
    protected function getViewData(): array
    {
        return array_merge($this->getStatusBanner(), [
            'title' => $this->statusLabel,
        ]);
    }
}
