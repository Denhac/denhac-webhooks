<?php

namespace App\Slack\Shortcuts;


use App\Events\DoorControlUpdated;
use App\Http\Requests\SlackRequest;
use App\WinDSX\Door;

class OpenWorkshopGlassDoor implements ShortcutInterface
{
    public static function callbackId(): string
    {
        return "door.open.door_dsx_3";
    }

    public static function handle(SlackRequest $request)
    {
        $customer = $request->customer();

        // TODO Verify if they're at the space and challenge if not
        if ($customer->hasCapability('denhac_can_verify_member_id')) {
            event(new DoorControlUpdated(5, Door::glassWorkshopDoor()->shouldOpen(true)));
        }
    }
}
