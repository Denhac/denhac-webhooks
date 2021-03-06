<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\Modals\ModalTrait;
use App\Slack\Modals\SuccessModal;
use App\Slack\SlackOptions;
use Illuminate\Support\Facades\Log;

class SlackInteractivityController extends Controller
{
    public function event(SlackRequest $request)
    {
        Log::info("Event!");
        Log::info(print_r($request->payload(), true));

        $type = $request->payload()['type'];
        if ($type == 'url_verification') {
            $challenge = $request->payload()['challenge'];

            return response($challenge);
        }

        return response('');
    }

    public function interactive(SlackRequest $request)
    {
//        Log::info("Interactive request!");
//        Log::info(print_r($request->payload(), true));

        $payload = $request->payload();
        $type = $payload['type'];

        if ($type == 'view_submission') {
            return $this->viewSubmission($request);
        } else if ($type == 'shortcut') {
            return $this->shortcut($request);
        } else {
            throw new \Exception('Slack interactive payload has unknown type: ' . $type);
        }
    }

    public function options(SlackRequest $request)
    {
//        Log::info("Options request!");
//        Log::info(print_r($request->payload(), true));

        $payload = $request->payload();

        if ($payload['type'] == 'block_suggestion') {
            return $this->blockSuggestion($request);
        }

        throw new \Exception('Slack options payload has unknown type');
    }

    private function blockSuggestion(SlackRequest $request)
    {
        $payload = $request->payload();

        $callback_id = $payload['view']['callback_id'];

        $modalClass = ModalTrait::getModal($callback_id);
        if (is_null($modalClass)) {
            throw new \Exception("Slack options payload has unknown callback id: $callback_id");
        }

        $options = $modalClass::getOptions($request);

        if (is_a($options, SlackOptions::class)) {
            $value = $request->payload()['value'] ?? null;
            $options->filterByValue($value);
        }

        return $options;
    }

    private function viewSubmission(SlackRequest $request)
    {
//        Log::info("View submitted!");
//        Log::info(print_r($request->payload(), true));

        $view = $request->payload()['view'];
        $callback_id = $view['callback_id'];

        $modalClass = ModalTrait::getModal($callback_id);

        if (is_null($modalClass)) {
            throw new \Exception("Slack interactive view submission had unknown callback id: $callback_id");
        }

        return $modalClass::handle($request);
    }

    private function shortcut(SlackRequest $request)
    {
        Log::info("Shortcut!");
        Log::info(print_r($request->payload(), true));

        $modal = new SuccessModal();
        $modal->open($request->payload()['trigger_id']);

        return response('');
    }
}
