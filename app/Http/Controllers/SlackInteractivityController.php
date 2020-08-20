<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\Modals\CancelMembershipConfirmationModal;
use App\Slack\Modals\MembershipOptionsModal;
use App\Slack\Modals\ModalInterface;
use App\Slack\Modals\NeedIdCheckModal;
use App\Slack\Modals\NewMemberIdCheckModal;
use Illuminate\Support\Facades\Log;

class SlackInteractivityController extends Controller
{
    private const MODALS = [
        MembershipOptionsModal::class,
        NeedIdCheckModal::class,
        NewMemberIdCheckModal::class,
        CancelMembershipConfirmationModal::class,
    ];

    public function interactive(SlackRequest $request)
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

    public function options(SlackRequest $request)
    {
        Log::info("Options request!");
        Log::info(print_r($request->payload(), true));

        $payload = $request->payload();

        if ($payload['type'] == 'block_suggestion') {
            return $this->blockSuggestion($request);
        }

        throw new \Exception("Slack options payload has unknown type");
    }

    private function blockSuggestion(SlackRequest $request)
    {
        $payload = $request->payload();

        $callback_id = $payload['view']['callback_id'];

        foreach (self::MODALS as $modalClass) {
            /** @var ModalInterface $modalClass */
            if ($callback_id == $modalClass::callbackId()) {
                return $modalClass::getOptions($request);
            }
        }

        throw new \Exception("Slack options payload has unknown callback id: $callback_id");
    }

    private function viewSubmission(SlackRequest $request)
    {
        $view = $request->payload()['view'];
        $callback_id = $view["callback_id"];

        foreach (self::MODALS as $modalClass) {
            /** @var ModalInterface $modalClass */
            if ($callback_id == $modalClass::callbackId()) {
                return $modalClass::handle($request);
            }
        }

        throw new \Exception("Slack interactive view submission had unknown callback id: $callback_id");
    }
}
