<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;

class IncidentCreatedMail extends BaseIncidentMail
{
    protected string $notificationType = 'created';

    public function __construct(Incident $incident, ?User $actor = null)
    {
        parent::__construct($incident, $actor ?? $incident->reporter);
        
        $this->extraFields = [
            'Reported By' => $incident->reporter?->name ?? 'N/A',
            'Status' => ucfirst($incident->status),
        ];
    }

    protected function getSubject(): string
    {
        return 'New Incident Reported – ' . $this->incident->incident_id;
    }

    protected function getView(): string
    {
        return 'emails.incident.created';
    }

    protected function getGreeting(): string
    {
        return 'Hello <span style="color: #0d47a1;">Team</span>,';
    }

    protected function getBody(): string
    {
        return 'A new incident has been reported. Please review and take appropriate action.';
    }
}
