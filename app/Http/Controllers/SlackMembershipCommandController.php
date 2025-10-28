<?php

namespace App\Http\Controllers;

use App\External\Slack\CommonResponses;
use App\External\Slack\Modals\EquipmentAuthorization;
use App\External\Slack\Modals\MembershipOptionsModal;
use App\Http\Requests\SlackRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use SlackPhp\BlockKit\Kit;
use Illuminate\Support\Facades\Log;

class SlackMembershipCommandController extends Controller
{
    public function __invoke(SlackRequest $request): Response|JsonResponse
    {
        $customer = $request->customer();
        if (is_null($customer)) {
            Log::info("Membership command invoked by unknown user {$request->getSlackId()}");
            return response()->json(Kit::message(
                text: CommonResponses::unrecognizedUser(),
            ));
        }

        $trigger_id = $request->get('trigger_id');

        $membershipOptions = MembershipOptionsModal::getMembershipOptions($customer);

        // If the user can only do an equipment authorization, jump directly to that modal screen to reduce friction.
        if($membershipOptions->count()) {
            $option = $membershipOptions->offsetGet(0);
            if($option->value == MembershipOptionsModal::EQUIPMENT_AUTHORIZATION_VALUE) {
                $modal = new EquipmentAuthorization($customer);
                $modal->open($trigger_id);

                return response('');
            }
        }

        $modal = new MembershipOptionsModal($customer, $membershipOptions);
        $modal->open($trigger_id);

        return response('');
    }
}
