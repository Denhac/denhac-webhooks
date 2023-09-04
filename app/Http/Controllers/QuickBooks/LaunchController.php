<?php

namespace App\Http\Controllers\QuickBooks;

use App\Http\Controllers\Controller;

class LaunchController extends Controller
{
    public function __invoke()
    {
        return view('quickbooks.launch');
    }
}
