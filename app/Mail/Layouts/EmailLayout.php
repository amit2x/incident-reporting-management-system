<?php

namespace App\Mail\Layouts;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

abstract class EmailLayout extends Mailable
{
    /**
     * The notification type for status banner color.
     */
    protected string $notificationType;

    /**
     * Get the status color based on notification type.
     */
    protected function getStatusColor(): string
    {
        return match ($this->notificationType) {
            'assigned' => '#0D47A1',      // Primary Blue
            'escalated' => '#FF8F00',     // Orange
            'resolved' => '#2E7D32',      // Green
            'closed' => '#2E7D32',        // Green
            'rejected' => '#DC2626',      // Red
            'mention' => '#7C3AED',       // Purple
            'comment' => '#0891B2',       // Sky Blue
            'created' => '#0D47A1',       // Dark Blue
            default => '#0D47A1',
        };
    }

    /**
     * Get the status label.
     */
    protected function getStatusLabel(): string
    {
        return match ($this->notificationType) {
            'assigned' => 'Incident Assigned',
            'escalated' => 'Incident Escalated',
            'resolved' => 'Incident Resolved',
            'closed' => 'Incident Closed',
            'rejected' => 'Incident Rejected',
            'mention' => 'You were mentioned',
            'comment' => 'New Comment Added',
            'created' => 'New Incident Reported',
            default => 'Incident Update',
        };
    }

    /**
     * Get the subject prefix.
     */
    protected function getSubjectPrefix(): string
    {
        return '[SAMANVAY]';
    }

    /**
     * Get the view data.
     */
    protected function getViewData(): array
    {
        return [
            'statusColor' => $this->getStatusColor(),
            'statusLabel' => $this->getStatusLabel(),
            'notificationType' => $this->notificationType,
        ];
    }
}