<?php

namespace App\Http\Controllers;

use App\Events\DoorControlUpdated;
use App\Http\Requests\SlackRequest;
use App\Slack\Modals\ModalTrait;
use App\Slack\Modals\SuccessModal;
use App\WinDSX\Door;
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

        $customer = $request->customer();

        if ($customer->hasCapability('denhac_can_verify_member_id')) {
            $callbackId = $request->payload()['callback_id'];

            if($callbackId == "door.open.workshop_main") {
                event(new DoorControlUpdated(5, Door::glassWorkshopDoor()));
            }
        }

//        $modal = new SuccessModal();
//        $modal->open($request->payload()['trigger_id']);

        return response('');
    }
}
