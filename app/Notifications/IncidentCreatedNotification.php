<?php

namespace App\Notifications;

use App\Mail\Incident\IncidentCreatedMail;
use App\Models\Incident;
use App\Models\User;

class IncidentCreatedNotification extends BaseNotification
{
    protected Incident $incident;

    protected User $actor;

    public function __construct(Incident $incident, ?User $actor = null)
    {
        $this->incident = $incident;
        $this->actor = $actor ?? $incident->reporter;
    }

    public function toMail($notifiable)
    {
        return (new IncidentCreatedMail($this->incident, $this->actor))
            ->to($notifiable->email);
    }

    protected function getDatabaseMessage(): string
    {
        return 'New incident '.$this->incident->incident_id.' has been reported';
    }

    protected function getDatabaseUrl(): string
    {
        return route('incidents.show', $this->incident);
    }

    protected function getDatabaseIcon(): string
    {
        return 'fa-plus-circle';
    }

    protected function getDatabaseType(): string
    {
        return 'incident_created';
    }
}
