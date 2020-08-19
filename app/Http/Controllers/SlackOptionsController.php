<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SlackOptionsController extends Controller
{
    public function __invoke(Request $request)
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
