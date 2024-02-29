<?php

namespace App\Http\Controllers;

use App\External\Slack\ClassFinder;
use App\External\Slack\Events\EventInterface;
use App\Http\Requests\SlackRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SlackEventController extends Controller
{
    public function __invoke(SlackRequest $request): Response
    {
        Log::channel('slack-events')->info(json_encode($request->json()));

        $event_class = ClassFinder::getEvent($request->json('event.type'));

        if (! is_null($event_class)) {
            /** @var EventInterface $event */
            $event = app($event_class);
            $event->handle($request);
        }

        return response('');
    }
}
