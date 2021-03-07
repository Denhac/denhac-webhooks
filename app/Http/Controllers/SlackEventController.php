<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\Events\EventTrait;
use Illuminate\Support\Facades\Log;

class SlackEventController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
        Log::info("Event!");
        Log::info(print_r($request->json(), true));

        $event = EventTrait::getEvent($request->json('event.type'));

        if (!is_null($event)) {
            $event->onQueue()->execute($request->json()['event']);
        }

        return response('');
    }
}
