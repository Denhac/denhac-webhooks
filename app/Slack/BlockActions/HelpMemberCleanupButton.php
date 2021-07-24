<?php

namespace App\Slack\BlockActions;


use App\Http\Requests\SlackRequest;
use App\Slack\Messages\SlackMemberCleanup;
use App\Slack\SlackApi;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Kit;

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
        /** @var SlackApi $api */
        $api = app(SlackApi::class);
        $slackId = $request->getSlackId();
        Log::info("Someone clicked the help button!");
        Log::info("Slack Id: ". $slackId);

        // Get a unique hash for that user and this cleanup action
        $hash_source = $slackId . self::blockId() . self::actionId();
        $channel_hash = substr(hash('sha256', $hash_source), 0, 8);
        $channel_name = "help-access-cleanup-$channel_hash";

        $channelIds = $api->conversations->toSlackIds($channel_name);

        if(! $channelIds->isEmpty()) {
            Log::info("We already have this channel!");
            return response('');
        }

        $channelId = $api->conversations->create($channel_name, true)['channel']['id'];

        $accessHelpers = explode(',', setting('slack.help.cleanup'));
        foreach($accessHelpers as $helper) {
            $api->conversations->invite($helper, $channelId);
        }

        $api->conversations->invite($slackId, $channel_name);

        $message = Kit::newMessage();
        $message->text("This is just to give us some info to start with:");
        $message->text(SlackMemberCleanup::getHelperMessage($slackId));
        $message->text("If you have anything else to add, you can reply here or you can wait until we look into the issue");
        $api->chat->postMessage($channelId, $message);

        return response('');
    }
}
