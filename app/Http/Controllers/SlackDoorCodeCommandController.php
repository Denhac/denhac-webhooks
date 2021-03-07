<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Http\Requests\SlackRequest;
use App\Slack\CommonResponses;
use Jeremeamia\Slack\BlockKit\Slack;

class SlackDoorCodeCommandController extends Controller
{
    const ACCESS_DOOR_CODE_KEY = 'access.door_code';

    public function __invoke(SlackRequest $request)
    {
        $member = $request->customer();

        if ($member === null) {
            return Slack::newMessage()->text(CommonResponses::unrecognizedUser());
        }

        if (!$member->member) {
            return Slack::newMessage()->text(CommonResponses::notAMemberInGoodStanding());
        }

        $text = $request->get('text', '');
        if ($text != '') {
            return $this->handleDoorCodeUpdate($request, $member, $text);
        }

        $doorCodeSetting = setting(self::ACCESS_DOOR_CODE_KEY);

        if ($doorCodeSetting != '') {
            return Slack::newMessage()
                ->text("Hello! The door access code is $doorCodeSetting.");
        } else {
            return Slack::newMessage()
                ->text("So here's the thing... I'd tell you the door code, but I seem to have misplaced it. Maybe ask an admin?");
        }
    }

    private function handleDoorCodeUpdate(SlackRequest $request, Customer $member, string $text)
    {
        if (!$member->isBoardMember()) {
            return Slack::newMessage()
                ->text('This functionality is for updating the door code, and only denhac board members can do that.');
        }

        if (preg_match("/^\d+$/", $text) == 1) {
            if (setting(self::ACCESS_DOOR_CODE_KEY) == $text) {
                return Slack::newMessage()
                    ->text("That's the same code we already have!");
            }

            setting([self::ACCESS_DOOR_CODE_KEY => $text])->save();

            return Slack::newMessage()
                ->text("Access code updated to $text!");

        } else {
            return Slack::newMessage()
                ->text("I'm sorry, that code didn't look to be in the right format. It needs to be all numbers.");
        }
    }
}
