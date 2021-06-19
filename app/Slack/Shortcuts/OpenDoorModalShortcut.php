<?php

namespace App\Slack\Shortcuts;


use App\Events\DoorControlUpdated;
use App\Http\Requests\SlackRequest;
use App\Slack\Modals\OpenDoorModal;
use App\WinDSX\Door;

class OpenDoorModalShortcut implements ShortcutInterface
{
    public static function callbackId(): string
    {
        return "door.open.modal";
    }

    public static function handle(SlackRequest $request)
    {
        // TODO Verify if they're at the space and challenge if not
        $modal = new OpenDoorModal();
        $modal->open($request->get('trigger_id'));
    }
}
