<?php

namespace App\View\Components\Email;

use App\Models\Incident;
use Illuminate\View\Component;

class IncidentCard extends Component
{
    public Incident $incident;
    public array $extraFields;

    public function __construct(Incident $incident, array $extraFields = [])
    {
        $this->incident = $incident;
        $this->extraFields = $extraFields;
    }

    public function render()
    {
        return view('emails.partials.incident-card');
    }
}