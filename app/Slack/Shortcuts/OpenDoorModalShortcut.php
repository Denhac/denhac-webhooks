<?php

namespace App\Slack\Shortcuts;


use App\Events\DoorControlUpdated;
use App\Http\Requests\SlackRequest;
use App\Slack\CommonResponses;
use App\Slack\Modals\OpenDoorModal;
use App\WinDSX\Door;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Kit;

class OpenDoorModalShortcut implements ShortcutInterface
{
    public static function callbackId(): string
    {
        return "door.open.modal";
    }

    public static function handle(SlackRequest $request)
    {
        $customer = $request->customer();

        if (is_null($customer)) {
            return Kit::newMessage()->text(CommonResponses::unrecognizedUser());
        }

        Log::info("Opening the door modal!");
        // TODO Verify if they're at the space and challenge if not
        $modal = new OpenDoorModal();
        $trigger_id = $request->payload()['trigger_id'];

        Log::info("Trigger ID: $trigger_id");
        $response = $modal->open($trigger_id);
        Log::info($response->getBody());

        return response('');
    }
}
