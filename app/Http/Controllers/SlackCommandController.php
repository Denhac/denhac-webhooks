<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Http\Requests\SlackSlashCommandRequest;
use App\Slack\SlackResponse;
use App\Subscription;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Slack;

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
        $member = $request->customer();

        if ($member === null) {
            return Slack::newMessage()
                ->text("I don't recognize you. If you're a member in good standing and you're not using paypal for membership dues, please contact access@denhac.org.");
        }

        if (!$member->member) {
            return Slack::newMessage()
                ->text("I recognize you but you don't appear to be a member in good standing. If you think this is a mistake, please contact access@denhac.org.");
        }

        $text = $request->get("text", "");
        if ($text != "") {
            return $this->handleDoorCodeUpdate($request, $member, $text);
        }

        $doorCodeSetting = setting(self::ACCESS_DOOR_CODE_KEY);

        if ($doorCodeSetting != "") {
            return Slack::newMessage()
                ->text("Hello! The door access code is $doorCodeSetting.");
        } else {
            return Slack::newMessage()
                ->text("So here's the thing... I'd tell you the door code, but I seem to have misplaced it. Maybe ask an admin?");
        }
    }

    private function handleDoorCodeUpdate(SlackSlashCommandRequest $request, Customer $member, string $text)
    {
        if (!$member->isBoardMember()) {
            return Slack::newMessage()
                ->text("This functionality is for updating the door code, and only denhac board members can do that.");
        }

        if (preg_match("/^\d+$/", $text) == 1) {
            if(setting(self::ACCESS_DOOR_CODE_KEY) == $text) {
                return Slack::newMessage()
                    ->text("That's the same code we already have!");
            }

            setting([self::ACCESS_DOOR_CODE_KEY => $text])->save();

            return Slack::newMessage()
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
            return Slack::newMessage()
                ->text("I'm sorry, that code didn't look to be in the right format. It needs to be all numbers.");
        }
    }

    public function interactive(Request $request)
    {
        Log::info("Interactive request!");
        Log::info($request->get("payload"));

        return response()->json([
            "replace_original" => "true",
            "text" => "Thanks for your request",
        ]);
    }

    public function options(Request $request)
    {
        Log::info("Options request!");
        Log::info($request->get("payload"));

        $options = [];

        $needIdCheckSubscriptions = Subscription::whereStatus('need-id-check')->with('customer')->get();

        foreach($needIdCheckSubscriptions as $subscription) {
            /** @var Subscription $subscription */
            /** @var Customer $customer */
            $customer = $subscription->customer;
            $subscription_id = $subscription->getKey();

            if(is_null($customer)) {
                $name = "Unknown Customer";
            } else {
                $name = "{$customer->first_name} {$customer->last_name}";
            }

            $options[] = [
                "text" => [
                    "type" => "plain_text",
                    "text" => "$name (Subscription #$subscription_id)"
                ],
                "value" => "subscription-$subscription_id",
            ];
        }

        return response()->json([
            "options" => $options,
        ]);
    }
}
