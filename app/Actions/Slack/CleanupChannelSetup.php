<?php

namespace App\Actions\Slack;

use App\Slack\Messages\SlackMemberCleanup;
use App\Slack\SlackApi;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Kit;
use Spatie\QueueableAction\QueueableAction;

class CleanupChannelSetup
{
    use QueueableAction;

    private SlackApi $api;

    /**
     * Create a new action instance.
     *
     * @return void
     */
    public function __construct(SlackApi $api)
    {
        $this->api = $api;
    }

    /**
     * Execute the action.
     *
     * @return mixed
     */
    public function execute($slackId)
    {
        // Get a unique hash for that user and this cleanup action
        $hash_source = $slackId . "help-access-cleanup";
        $channel_hash = substr(hash('sha256', $hash_source), 0, 8);
        $channel_name = "help-access-cleanup-$channel_hash";

        $channelIds = $this->api->conversations->toSlackIds($channel_name);

        if (!$channelIds->isEmpty()) {
            Log::info("We already have this channel!");
            return response('');
        }

        $channelId = $this->api->conversations->create($channel_name, true)['channel']['id'];

        $accessHelpers = explode(',', setting('slack.help.cleanup'));
        foreach ($accessHelpers as $helper) {
            $this->api->conversations->invite($helper, $channelId);
        }

        $this->api->conversations->invite($slackId, $channel_name);

        $message = Kit::newMessage();
        $message->text("This is just to give us some info to start with:");
        $message->text(SlackMemberCleanup::getHelperMessage($slackId, false));
        $message->text("If you have anything else to add, you can reply here or you can wait until we look into the issue.");
        $this->api->chat->postMessage($channelId, $message);
    }
}
