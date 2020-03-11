<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Http\Requests\SlackSlashCommandRequest;
use App\WooCommerce\Api\WooCommerceApi;

class SlackCommandController extends Controller
{
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
        $id = $request->get("user_id");
        /** @var Customer $member */
        $member = Customer::whereSlackId($id)->first();

        if($member === null) {
            return response()->json([
                "response_type" => "ephemeral",
                "text" => "I don't recognize you. If you're a member in good standing and you're not using paypal for membership dues, please contact access@denhac.org.",
            ]);
        }

        if(! $member->member) {
            return response()->json([
                "response_type" => "ephemeral",
                "text" => "I recognize you but you don't appear to be a member in good standing. If you think this is a mistake, please contact access@denhac.org.",
            ]);
        }

        return response()->json([
            "response_type" => "ephemeral",
            "text" => "Hello! The door access code is ".config('denhac.door_code').".",
        ]);
    }
}
