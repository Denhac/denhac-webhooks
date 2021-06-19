<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\ClassFinder;
use App\Slack\Events\EventInterface;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class SlackEventController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
        Log::info("Event!");
        Log::info(print_r($request->json(), true));

        $event_class = ClassFinder::getEvent($request->json('event.type'));

        if (!is_null($event_class)) {
            /** @var EventInterface $event */
            $event = app($event_class);
            $event->handle($request);
        }

        return response('');
    }
}
