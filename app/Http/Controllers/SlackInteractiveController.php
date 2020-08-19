<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SlackInteractiveController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info("Interactive request!");
        Log::info($request->get("payload"));

        return response()->json([
            "replace_original" => "true",
            "text" => "Thanks for your request",
        ]);
    }
}
