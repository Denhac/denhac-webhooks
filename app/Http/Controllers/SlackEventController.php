<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use Illuminate\Support\Facades\Log;

class SlackEventController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
        Log::info("Event!");
        Log::info(print_r($request->json(), true));

        return response('');
    }
}
