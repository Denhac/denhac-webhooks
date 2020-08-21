<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackRequest;
use App\Slack\Modals\MembershipOptionsModal;
use App\Slack\SlackApi;
use Jeremeamia\Slack\BlockKit\Slack;

class SlackMembershipCommandController extends Controller
{
    /**
     * @var SlackApi
     */
    private $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function __invoke(SlackRequest $request)
    {
        $customer = $request->customer();

        if ($customer === null) {
            return Slack::newMessage()
                ->text("I don't recognize you. If you're a member in good standing and you're not using paypal for membership dues, please contact access@denhac.org.");
        }

        $modal = new MembershipOptionsModal();
        $modal->open($request->get('trigger_id'));

        return response("");
    }
}
