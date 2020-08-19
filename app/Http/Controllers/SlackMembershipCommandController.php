<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\SlackApi;
use App\Slack\SlackID;
use App\Subscription;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Slack;

class SlackMembershipCommandController extends Controller
{
    /**
     * @var SlackApi
     */
    private $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function __invoke(SlackRequest $request)
    {
        Log::info($request->getContent());

        $customer = $request->customer();

        if ($customer === null) {
            return Slack::newMessage()
                ->text("I don't recognize you. If you're a member in good standing and you're not using paypal for membership dues, please contact access@denhac.org.");
        }

        $modalView = Slack::newModal()
            ->callbackId(SlackID::MEMBERSHIP_MODAL_CALLBACK_ID)
            ->title("What do you want to do?")
            ->submit("Submit");

        $modalView->newInput()
            ->label("Membership Option")
            ->blockId(SlackID::MEMBERSHIP_OPTION_BLOCK_ID)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(SlackID::MEMBERSHIP_OPTION_ACTION_ID)
            ->placeholder("Select an Item")
            ->minQueryLength(0);

        $this->slackApi->views_open($request->get('trigger_id'), $modalView);

        return response("");
    }

    public function signUpNewMember(SlackRequest $request)
    {
        $modalView = Slack::newModal()
            ->callbackId(SlackID::SIGN_UP_NEW_MEMBER_CALLBACK_ID)
            ->title("New Member Signup")
            ->submit("Submit");

        $modalView->newInput()
            ->label("New Member")
            ->blockId(SlackID::SIGN_UP_NEW_MEMBER_BLOCK_ID)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(SlackID::SIGN_UP_NEW_MEMBER_ACTION_ID)
            ->placeholder("Select a Customer")
            ->minQueryLength(0);

        return response()->json([
            "response_action" => "push",
            "view" => $modalView,
        ]);
    }

    public function signUpNewMemberDetails(SlackRequest $request)
    {
        $selectedOption = $request->payload()['view']['state']['values']
        [SlackID::SIGN_UP_NEW_MEMBER_BLOCK_ID][SlackID::SIGN_UP_NEW_MEMBER_ACTION_ID]
        ['selected_option']['value'];

        Log::info("Selected Options is: {$selectedOption}");

        $matches = [];
        $result = preg_match('/subscription\-(\d+)/', $selectedOption, $matches);

        if(! $result) {
            throw new \Exception("Option wasn't valid for subscription: $selectedOption");
        }

        /** @var Subscription $subscription */
        $subscription = Subscription::findOrFail($matches[1]);
        $customer = $subscription->customer;

        $modalView = Slack::newModal()
            ->callbackId(SlackID::NEW_MEMBER_DETAIL_CALLBACK_ID)
            ->title("New Member Signup")
            ->submit("Submit");

        $modalView->newInput()
            ->blockId(SlackID::NEW_MEMBER_DETAIL_FIRST_NAME_BLOCK_ID)
            ->label("First Name")
            ->newTextInput(SlackID::NEW_MEMBER_DETAIL_FIRST_NAME_ACTION_ID)
            ->initialValue($customer->first_name);

        $modalView->newInput()
            ->blockId(SlackID::NEW_MEMBER_DETAIL_LAST_NAME_BLOCK_ID)
            ->label("Last Name")
            ->newTextInput(SlackID::NEW_MEMBER_DETAIL_LAST_NAME_ACTION_ID)
            ->initialValue($customer->last_name);

        $modalView->newInput()
            ->blockId(SlackID::NEW_MEMBER_DETAIL_CARD_NUM_BLOCK_ID)
            ->label("Card Number")
            ->newTextInput(SlackID::NEW_MEMBER_DETAIL_CARD_NUM_ACTION_ID)
            ->placeholder("Enter Card Number");

        return response()->json([
            "response_action" => "push",
            "view" => $modalView,
        ]);
    }
}
