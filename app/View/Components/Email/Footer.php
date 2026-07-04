<?php

namespace App\View\Components\Email;

use Illuminate\View\Component;

class Footer extends Component
{
    public function render()
    {
        return view('emails.partials.footer');
    }
}