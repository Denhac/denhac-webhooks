<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\Events\EventInterface;
use App\Slack\Events\EventTrait;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class SlackEventController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
        Log::info("Event!");
        Log::info(print_r($request->json(), true));

        $event_class = $this->getEvent($request->json('event.type'));

        if (!is_null($event_class)) {
            /** @var EventInterface $event */
            $event = app($event_class);
            $event->handle($request->json('event'));
        }

        return response('');
    }

    public function getEvent($eventType)
    {
        return collect(get_declared_classes())
            ->filter(function ($name) use ($eventType) {
                if (!str_starts_with($name, 'App\\Slack\\Events')) {
                    return false;
                }

                $reflect = new ReflectionClass($name);
                if (! $reflect->implementsInterface(\App\Slack\Events\EventInterface::class)) {
                    return false;
                }

                if ($name::eventType() !== $eventType) {
                    return false;
                }

                return true;
            })
            ->first();
    }
}
