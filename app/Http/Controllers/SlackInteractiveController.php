<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\SlackID;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\Facades\Log;

class SlackInteractiveController extends Controller
{
    /**
     * @var WooCommerceApi
     */
    private $wooCommerceApi;

    public function __construct(WooCommerceApi $wooCommerceApi)
    {
        $this->wooCommerceApi = $wooCommerceApi;
    }

    public function __invoke(SlackRequest $request)
    {
        Log::info("Interactive request!");
        Log::info(print_r($request->payload(), true));

        $payload = $request->payload();

        if ($payload['type'] == 'view_submission') {
            return $this->viewSubmission($request);
        } else {
            throw new \Exception("Slack interactive payload has unknown type");
        }
    }

    private function viewSubmission(SlackRequest $request)
    {
        $view = $request->payload()['view'];
        $callback_id = $view["callback_id"];

        switch ($callback_id) {
            case SlackID::MEMBERSHIP_MODAL_CALLBACK_ID:
                return $this->membershipModal($request);
            case SlackID::SIGN_UP_NEW_MEMBER_CALLBACK_ID:
                return $this->newMemberModal($request);
            case SlackID::NEW_MEMBER_DETAIL_CALLBACK_ID:
                return $this->newMemberDetailModal($request);
        }

        throw new \Exception("Slack interactive view submission had unknown callback id: $callback_id");
    }

    private function membershipModal(SlackRequest $request)
    {
        $selectedOption = $request->payload()['view']['state']['values']
        [SlackID::MEMBERSHIP_OPTION_BLOCK_ID][SlackID::MEMBERSHIP_OPTION_ACTION_ID]
        ['selected_option']['value'];

        /** @var SlackMembershipCommandController $membershipController */
        $membershipController = app(SlackMembershipCommandController::class);

        switch ($selectedOption) {
            case SlackID::SIGN_UP_NEW_MEMBER_VALUE:
                return $membershipController->signUpNewMember($request);
        }

        throw new \Exception("Slack membership model had unknown selected option: $selectedOption");
    }

    private function newMemberModal(SlackRequest $request)
    {
        /** @var SlackMembershipCommandController $membershipController */
        $membershipController = app(SlackMembershipCommandController::class);

        return $membershipController->signUpNewMemberDetails($request);
    }

    private function newMemberDetailModal(SlackRequest $request)
    {
        Log::info("Payload:");
        Log::info($request->get('payload'));
        $firstName = $request->payload()['view']['state']['values']
        [SlackID::NEW_MEMBER_DETAIL_FIRST_NAME_BLOCK_ID][SlackID::NEW_MEMBER_DETAIL_FIRST_NAME_ACTION_ID]['value'];
        $lastName = $request->payload()['view']['state']['values']
        [SlackID::NEW_MEMBER_DETAIL_LAST_NAME_BLOCK_ID][SlackID::NEW_MEMBER_DETAIL_LAST_NAME_ACTION_ID]['value'];
        $birthday = $request->payload()['view']['state']['values']
        [SlackID::NEW_MEMBER_DETAIL_BIRTHDAY_BLOCK_ID][SlackID::NEW_MEMBER_DETAIL_BIRTHDAY_ACTION_ID]['selected_date'];
        $cards = $request->payload()['view']['state']['values']
        [SlackID::NEW_MEMBER_DETAIL_CARD_NUM_BLOCK_ID][SlackID::NEW_MEMBER_DETAIL_CARD_NUM_ACTION_ID]['value'];

        $customerId = $request->payload()['view']['private_metadata'];
        Log::info($customerId);
        Log::info($firstName);
        Log::info($lastName);
        Log::info($birthday);
        Log::info($cards);

        $this->wooCommerceApi->customers
            ->update($customerId, [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "meta_data" => [
                    [
                        "key" => "access_card_number",
                        "value" => $cards,
                    ],
                    [
                        "key" => "account_birthday",
                        "value" => $birthday,
                    ]
                ],
            ]);

        return response()->json([
            "response_action" => "clear",
        ]);
    }
}
