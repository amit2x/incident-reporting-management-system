<?php

namespace App\View\Components\Email;

use Illuminate\View\Component;

class InfoRow extends Component
{
    public string $label;
    public string $value;
    public ?string $color;

    public function __construct(string $label, string $value, ?string $color = null)
    {
        $this->label = $label;
        $this->value = $value;
        $this->color = $color;
    }

    public function render()
    {
        return view('emails.partials.info-row');
    }
}