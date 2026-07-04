<?php

namespace App\View\Components\Email;

use Illuminate\View\Component;

class StatusBanner extends Component
{
    public string $color;
    public string $label;
    public ?string $incidentId;

    public function __construct(string $color, string $label, ?string $incidentId = null)
    {
        $this->color = $color;
        $this->label = $label;
        $this->incidentId = $incidentId;
    }

    public function render()
    {
        return view('emails.partials.status-banner');
    }
}