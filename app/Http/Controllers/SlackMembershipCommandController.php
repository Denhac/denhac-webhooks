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
            Log::info('MembershipCommand: Rejecting command from unrecognised slack id '.$request->getSlackId());
            return Kit::newMessage()->text(CommonResponses::unrecognizedUser());
        }

        $modal = new MembershipOptionsModal();
        $modal->open($request->get('trigger_id'));

        return response('');
    }
}
