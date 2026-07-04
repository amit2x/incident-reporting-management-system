<?php

namespace App\Mail\Incident;

use App\Models\Incident;
use App\Models\User;
use App\Models\Escalation;

class IncidentEscalatedMail extends BaseIncidentMail
{
    protected string $notificationType = 'escalated';
    protected ?Escalation $escalation;

    public function __construct(Incident $incident, User $actor, Escalation $escalation)
    {
        parent::__construct($incident, $actor);
        $this->escalation = $escalation;
        
        $this->extraFields = [
            'Escalation Level' => $escalation->level ?? 'N/A',
            'Escalated To' => $escalation->escalatedTo?->name ?? 'N/A',
            'Reason' => $escalation->reason ?? 'N/A',
        ];
    }

    protected function getSubject(): string
    {
        return 'Incident Escalated – Immediate Attention Required';
    }

    protected function getView(): string
    {
        return 'emails.incident.escalated';
    }

    protected function getGreeting(): string
    {
        return 'Hello <span style="color: #0d47a1;">' . 
               ($this->actor?->name ?? 'User') . 
               '</span>,';
    }

    protected function getBody(): string
    {
        return 'An incident has been escalated to you. Immediate attention is required.';
    }
}