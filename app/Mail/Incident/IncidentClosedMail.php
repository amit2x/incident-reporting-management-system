<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;

class IncidentClosedMail extends BaseIncidentMail
{
    protected string $notificationType = 'closed';

    public function __construct(Incident $incident, User $actor)
    {
        parent::__construct($incident, $actor);
        
        $this->extraFields = [
            'Closed By' => $actor->name,
            'Closed At' => $incident->closed_at?->format('d M Y, H:i') ?? 'N/A',
        ];
    }

    protected function getSubject(): string
    {
        return 'Incident Closed – ' . $this->incident->incident_id;
    }

    protected function getView(): string
    {
        return 'emails.incident.closed';
    }

    protected function getGreeting(): string
    {
        return 'Hello <span style="color: #0d47a1;">' . 
               ($this->incident->reporter?->name ?? 'User') . 
               '</span>,';
    }

    protected function getBody(): string
    {
        return 'The incident you reported has been closed. Thank you for your cooperation.';
    }
}