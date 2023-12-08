<?php

namespace App\Http\Controllers\QuickBooks;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LaunchController extends Controller
{
    public function __invoke(): View
    {
        return view('quickbooks.launch');
    }
}
