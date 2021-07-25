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
            return $this->blockAction($request);
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

    private function blockAction(SlackRequest $request)
    {
        Log::info("Block Action!");
        Log::info(print_r($request->payload(), true));


        $actions = $request->payload()['actions'];

        foreach ($actions as $action) {
            $blockId = $action['block_id'];
            $actionId = $action['action_id'];

            $blockAction = ClassFinder::getBlockAction($blockId, $actionId);

            if (is_null($blockAction)) {
                throw new \Exception("No block action handled for {$blockId} and {$actionId}");
            }

            return $blockAction::handle($request);
        }

        throw new \Exception("No actions found in ".print_r($request->payload(), true));

//        return $shortcutClass::handle($request);
    }
}
