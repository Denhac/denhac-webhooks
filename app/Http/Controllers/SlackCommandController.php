<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Http\Requests\SlackSlashCommandRequest;
use App\Slack\SlackResponse;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SlackCommandController extends Controller
{
    const ACCESS_DOOR_CODE_KEY = 'access.door_code';
    /**
     * @var WooCommerceApi
     */
    private $wooCommerceApi;

    public function __construct(WooCommerceApi $wooCommerceApi)
    {
        $this->wooCommerceApi = $wooCommerceApi;
    }

    public function doorCode(SlackSlashCommandRequest $request)
    {
        $id = $request->get('user_id');
        /** @var Customer $member */
        $member = Customer::whereSlackId($id)->first();

        if ($member === null) {
            return (new SlackResponse())
                ->text("I don't recognize you. If you're a member in good standing and you're not using paypal for membership dues, please contact access@denhac.org.");
        }

        if (!$member->member) {
            return (new SlackResponse())
                ->text("I recognize you but you don't appear to be a member in good standing. If you think this is a mistake, please contact access@denhac.org.");
        }

        $text = $request->get("text", "");
        if ($text != "") {
            return $this->handleDoorCodeUpdate($request, $member, $text);
        }

        $doorCodeSetting = setting(self::ACCESS_DOOR_CODE_KEY);

        if ($doorCodeSetting != "") {
            return (new SlackResponse())
                ->text("Hello! The door access code is $doorCodeSetting.");
        } else {
            return (new SlackResponse())
                ->text("So here's the thing... I'd tell you the door code, but I seem to have misplaced it. Maybe ask an admin?");
        }
    }

    private function handleDoorCodeUpdate(SlackSlashCommandRequest $request, Customer $member, string $text)
    {
        if (!$member->hasCapability("denhac_board_member")) {
            return (new SlackResponse())
                ->text("This functionality is for updating the door code, and only denhac board members can do that.");
        }

        if (preg_match("/^\d+$/", $text) == 1) {
            if(setting(self::ACCESS_DOOR_CODE_KEY) == $text) {
                return (new SlackResponse())
                    ->text("That's the same code we already have!");
            }

            setting([self::ACCESS_DOOR_CODE_KEY => $text])->save();

            return (new SlackResponse())
                ->text("Access code updated to $text!");

                    /*
                    [
                        "type" => "actions",
                        "elements" => [
                            [
                                "type" => "button",
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => "Email it out!",
                                ],
                                "action_id" => "1",
                                "style" => "primary",
                            ],
                            [
                                "type" => "button",
                                "text" => [
                                    "type" => "plain_text",
                                    "text" => "No, I'll do it.",
                                ],
                                "action_id" => "2",
                            ]
                        ]
                    ]
                    */
        } else {
            return (new SlackResponse())
                ->text("I'm sorry, that code didn't look to be in the right format. It needs to be all numbers.");
        }
    }

    public function interactive(Request $request)
    {
        Log::info("Request!");
        Log::info($request->get("payload"));

        return response()->json([
            "replace_original" => "true",
            "text" => "Thanks for your request",
        ]);
    }
}
