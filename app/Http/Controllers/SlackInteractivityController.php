<?php

namespace App\Http\Controllers;

use App\External\Slack\BlockActions\BlockActionInterface;
use App\External\Slack\BlockActions\BlockActionStatic;
use App\External\Slack\ClassFinder;
use App\Http\Requests\SlackRequest;
use Illuminate\Support\Facades\Log;

class SlackInteractivityController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
        Log::channel('slack-interactive')->info(print_r($request->getPayload(), true));

        $payload = $request->payload();
        $type = $payload['type'];

        if ($type == 'view_submission') {
            return $this->viewSubmission($request);
        } elseif ($type == 'shortcut') {
            return $this->shortcut($request);
        } elseif ($type == 'block_actions') {
            return $this->blockAction($request);
        } else {
            throw new \Exception('Slack interactive payload has unknown type: '.$type);
        }
    }

    private function viewSubmission(SlackRequest $request)
    {
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
        $callbackId = $request->payload()['callback_id'];
        $shortcutClass = ClassFinder::getShortcut($callbackId);

        if (is_null($shortcutClass)) {
            throw new \Exception("Slack interactive shortcut had unknown callback id: $callbackId");
        }

        return $shortcutClass::handle($request);
    }

    private function blockAction(SlackRequest $request)
    {
        $payload = $request->payload();
        $actions = $payload['actions'];

        // If we're a modal, try to find a block action on the modal
        if (array_key_exists('view', $payload) && $payload['view']['type'] == 'modal') {
            $view = $request->payload()['view'];
            $callbackId = $view['callback_id'];

            $modalClass = ClassFinder::getModal($callbackId);

            if (method_exists($modalClass, 'getBlockActions')) {
                $modalBlockActions = $modalClass::getBlockActions();
                foreach ($actions as $action) {
                    $blockId = $action['block_id'];
                    $actionId = $action['action_id'];
                    /** @var BlockActionInterface $blockAction */
                    foreach ($modalBlockActions as $blockAction) {
                        if ($blockAction->blockId() == $blockId && $blockAction->actionId() == $actionId) {
                            Log::info('We found a match for that modal, block, and action combo.');

                            return $blockAction->handle($request);
                        }
                    }
                }
            }
        }

        // Otherwise, try to find a standalone block action
        foreach ($actions as $action) {
            $blockId = $action['block_id'];
            $actionId = $action['action_id'];

            /** @var BlockActionStatic $blockAction */
            $blockAction = ClassFinder::getBlockAction($blockId, $actionId);

            if (is_null($blockAction)) {
                throw new \Exception("No block action handled for {$blockId} and {$actionId}");
            }

            return $blockAction::handle($request);
        }

        throw new \Exception('No actions found in '.print_r($payload, true));
    }
}
