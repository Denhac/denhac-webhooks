<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\ClassFinder;
use Illuminate\Support\Facades\Log;

class SlackInteractivityController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
//        Log::info("Interactive request!");
//        Log::info(print_r($request->payload(), true));

        $payload = $request->payload();
        $type = $payload['type'];

        if ($type == 'view_submission') {
            return $this->viewSubmission($request);
        } else if ($type == 'shortcut') {
            return $this->shortcut($request);
        } else if ($type == 'block_actions') {
            Log::info("Interactive request!");
            Log::info(print_r($request->payload(), true));
            return response()->json(); // 200 OK so it doesn't error
        } else {
            throw new \Exception('Slack interactive payload has unknown type: ' . $type);
        }
    }

    private function viewSubmission(SlackRequest $request)
    {
//        Log::info("View submitted!");
//        Log::info(print_r($request->payload(), true));

        $view = $request->payload()['view'];
        $callbackId = $view['callback_id'];

        $modalClass = ClassFinder::getModal($callbackId);

        if (is_null($modalClass)) {
            throw new \Exception("Slack interactive view submission had unknown callback id: $callbackId");
        }

        return $modalClass::handle($request);
    }

    private function shortcut(SlackRequest $request)
    {
        Log::info("Shortcut!");
        Log::info(print_r($request->payload(), true));

        $callbackId = $request->payload()['callback_id'];
        $shortcutClass = ClassFinder::getShortcut($callbackId);

        if (is_null($shortcutClass)) {
            throw new \Exception("Slack interactive shortcut had unknown callback id: $callbackId");
        }

        return $shortcutClass::handle($request);
    }
}
