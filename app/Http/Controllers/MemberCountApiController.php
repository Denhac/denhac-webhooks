<?php

namespace App\Http\Controllers;

use App\Models\Customer;

class MemberCountApiController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            "members" => Customer::where('member', true)->count(),
        ]);
    }
}
