<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;

class IncidentAssignedMail extends BaseIncidentMail
{
    protected string $notificationType = 'assigned';

    public function __construct(Incident $incident, User $actor, ?User $assignee = null)
    {
        parent::__construct($incident, $actor);
        
        $this->extraFields = [
            'Assigned To' => $assignee?->name ?? 'N/A',
        ];
    }

    protected function getSubject(): string
    {
        return 'Incident Assigned – ' . $this->incident->incident_id;
    }

    protected function getView(): string
    {
        return 'emails.incident.assigned';
    }

    protected function getGreeting(): string
    {
        return 'Hello <span style="color: #0d47a1;">' . 
               ($this->actor?->name ?? 'User') . 
               '</span>,';
    }

    protected function getBody(): string
    {
        return 'An incident has been assigned to you. Please review the details and take appropriate action.';
    }
}