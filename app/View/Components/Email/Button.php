<?php

namespace App\View\Components\Email;

use Illuminate\View\Component;

class Button extends Component
{
    public string $url;
    public string $text;
    public string $type;
    public ?string $icon;

    public function __construct(
        string $url, 
        string $text, 
        string $type = 'primary',
        ?string $icon = null
    ) {
        $this->url = $url;
        $this->text = $text;
        $this->type = $type;
        $this->icon = $icon;
    }

    public function render()
    {
        return view('emails.partials.button');
    }
}