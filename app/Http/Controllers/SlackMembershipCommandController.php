<?php

namespace App\Http\Controllers;

use App\External\Slack\CommonResponses;
use App\External\Slack\Modals\MembershipOptionsModal;
use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;
use Illuminate\Support\Facades\Log;

class SlackMembershipCommandController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
        $customer = $request->customer();
        if ($customer === null) {
            Log::info("Membership command invoked by unknown user {$request->getSlackId()}");
            return Kit::message(
                text: CommonResponses::unrecognizedUser(),
            );
        }

        $modal = new MembershipOptionsModal($customer);
        $modal->open($request->get('trigger_id'));

        return response('');
    }
}
