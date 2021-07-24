<?php

namespace App\Slack\BlockActions;


use App\Http\Requests\SlackRequest;
use Illuminate\Support\Facades\Log;

class HelpMemberCleanupButton implements BlockActionInterface
{

    public static function blockId(): string
    {
        return "block-member-cleanup-help";
    }

    public static function actionId(): string
    {
        return "action-member-cleanup-help";
    }

    public static function handle(SlackRequest $request)
    {
        Log::info("Someone clicked the help button!");
        Log::info("Slack Id: ".$request->getSlackId());

        $customer = $request->customer();
        if(! is_null($customer)) {
            Log::info("Username: ".$request->customer()->username);
            Log::info("Slack Id: ".$request->customer()->slack_id);
        }
    }
}
