<?php

namespace App\View\Components\Email;

use Illuminate\View\Component;

class Header extends Component
{
    public function render()
    {
        return view('emails.partials.header');
    }
}