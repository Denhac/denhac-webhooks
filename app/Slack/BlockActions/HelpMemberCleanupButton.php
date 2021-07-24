<?php

namespace App\Slack\BlockActions;


use App\Actions\Slack\CleanupChannelSetup;
use App\Http\Requests\SlackRequest;
use App\Slack\SlackApi;
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
        $slackId = $request->getSlackId();
        Log::info("Someone clicked the help button!");
        Log::info("Slack Id: ". $slackId);

        app(CleanupChannelSetup::class)
            ->onQueue()
            ->execute($slackId);

        return response('');
    }
}
