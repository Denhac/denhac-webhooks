<?php

namespace App\External\Slack\Shortcuts;

use App\External\Slack\CommonResponses;
use App\External\Slack\Modals\OpenDoorModal;
use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;

class OpenDoorModalShortcut implements ShortcutInterface
{
    public static function callbackId(): string
    {
        return 'door.open.modal';
    }

    public static function handle(SlackRequest $request)
    {
        $customer = $request->customer();

        if (is_null($customer)) {
            return Kit::message(
                text: CommonResponses::unrecognizedUser(),
            );
        }

        $modal = new OpenDoorModal;
        $trigger_id = $request->payload()['trigger_id'];

        $modal->open($trigger_id);

        return response('');
    }
}
