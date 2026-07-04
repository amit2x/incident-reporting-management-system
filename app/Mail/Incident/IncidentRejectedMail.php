<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;

class IncidentRejectedMail extends BaseIncidentMail
{
    protected string $notificationType = 'rejected';

    public function __construct(Incident $incident, User $actor)
    {
        parent::__construct($incident, $actor);
        
        $this->extraFields = [
            'Rejected By' => $actor->name,
            'Rejection Reason' => $incident->rejection_reason ?? 'N/A',
        ];
    }

    protected function getSubject(): string
    {
        return 'Incident Rejected – ' . $this->incident->incident_id;
    }

    protected function getView(): string
    {
        return 'emails.incident.rejected';
    }

    protected function getGreeting(): string
    {
        return 'Hello <span style="color: #0d47a1;">' . 
               ($this->incident->reporter?->name ?? 'User') . 
               '</span>,';
    }

    protected function getBody(): string
    {
        return 'The incident you reported has been rejected. Please review the reason and take necessary action.';
    }
}