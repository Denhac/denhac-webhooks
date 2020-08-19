<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\SlackID;
use Illuminate\Support\Facades\Log;

class SlackInteractiveController extends Controller
{
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
    }

    private function newMemberModal(SlackRequest $request)
    {
        /** @var SlackMembershipCommandController $membershipController */
        $membershipController = app(SlackMembershipCommandController::class);

        return $membershipController->signUpNewMemberDetails($request);
    }
}
