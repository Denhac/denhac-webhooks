<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackEventRequest;
use App\Jobs\HandleSlackEventChannelCreated;
use App\Jobs\HandleSlackEventMemberJoinedChannel;

class SlackEventController extends Controller
{
    public function event(SlackEventRequest $request)
    {
        $type = $request->get("type");
        $payload = json_decode($request->getContent(), true);

        switch ($type) {
            case 'url_verification':
                return $this->handle_url_verification($request);
            case 'event_callback':
                return $this->handle_event_callback($payload);
        }

        return response()->json();
    }

    private function handle_url_verification(SlackEventRequest $request)
    {
        return $request->get("challenge");
    }

    private function handle_event_callback($payload)
    {
        $event = $payload["event"];
        switch ($event["type"]) {
            case "channel_created":
                $this->dispatch(new HandleSlackEventChannelCreated($event));
                break;
            case "member_joined_channel":
                $this->dispatch(new HandleSlackEventMemberJoinedChannel($event));
                break;
        }

        return response()->json();
    }
}
