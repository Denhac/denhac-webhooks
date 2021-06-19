<?php

namespace App\Slack\Shortcuts;


use App\Http\Requests\SlackRequest;
use App\Slack\CommonResponses;
use App\Slack\Modals\OpenDoorModal;
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

        $modal = new OpenDoorModal();
        $trigger_id = $request->payload()['trigger_id'];

        $modal->open($trigger_id);

        return response('');
    }
}
