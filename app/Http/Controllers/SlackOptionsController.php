<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Http\Requests\SlackRequest;
use App\Slack\SlackID;
use App\Slack\SlackOptions;
use App\Subscription;
use Illuminate\Support\Facades\Log;

class SlackOptionsController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
//        Log::info("Options request!");
//        Log::info(print_r($request->payload(), true));

        $payload = $request->payload();

        if($payload['type'] == 'block_suggestion') {
            return $this->blockSuggestion($request);
        }

        throw new \Exception("Slack options payload has unknown type");
    }

    private function blockSuggestion(SlackRequest $request)
    {
        $payload = $request->payload();

        $action_id = $payload['action_id'];

        switch ($action_id) {
            case SlackID::MEMBERSHIP_OPTION_ACTION_ID:
                return $this->getMembershipOptions($request);
            case SlackID::SIGN_UP_NEW_MEMBER_ACTION_ID:
                return $this->getNeedIdCheckUsers();
        }

        throw new \Exception("Slack options payload has unknown action id");
    }

    private function getMembershipOptions(SlackRequest $request)
    {
        $options = SlackOptions::new();

        $customer = $request->customer();

        if(is_null($customer)) {
            return $options;
        }

        if($customer->isBoardMember()) {
            $options
                ->option("Sign up new member", SlackID::SIGN_UP_NEW_MEMBER_VALUE);
        }

        return $options;
    }

    private function getNeedIdCheckUsers()
    {
        $options = new SlackOptions;

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

            $text = "$name (Subscription #$subscription_id)";
            $value = "subscription-$subscription_id";

            $options->option($text, $value);
        }

        return $options;
    }
}
