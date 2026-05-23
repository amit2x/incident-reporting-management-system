<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    /**
     * Show the help/manual page for guest users.
     */
    public function index()
    {
        return view('guest.help');
    }

    /**
     * Show the features page.
     */
    public function features()
    {
        return view('guest.features');
    }
}
