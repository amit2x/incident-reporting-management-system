<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;

class IncidentResolvedMail extends BaseIncidentMail
{
    protected string $notificationType = 'resolved';

    public function __construct(Incident $incident, User $actor)
    {
        parent::__construct($incident, $actor);
        
        $this->extraFields = [
            'Resolved By' => $actor->name,
            'Resolved At' => $incident->resolved_at?->format('d M Y, H:i') ?? 'N/A',
        ];
    }

    protected function getSubject(): string
    {
        return 'Incident Resolved – ' . $this->incident->incident_id;
    }

    protected function getView(): string
    {
        return 'emails.incident.resolved';
    }

    protected function getGreeting(): string
    {
        return 'Hello <span style="color: #0d47a1;">' . 
               ($this->incident->reporter?->name ?? 'User') . 
               '</span>,';
    }

    protected function getBody(): string
    {
        return 'The incident you reported has been resolved. Please verify the resolution.';
    }
}