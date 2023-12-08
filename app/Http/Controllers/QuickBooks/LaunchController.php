<?php

namespace App\Http\Controllers\QuickBooks;

use Illuminate\View\View;
use App\Http\Controllers\Controller;

class LaunchController extends Controller
{
    public function __invoke(): View
    {
        return view('quickbooks.launch');
    }
}
