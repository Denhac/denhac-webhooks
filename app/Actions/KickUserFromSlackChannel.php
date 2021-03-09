<?php

namespace App\Actions;

use App\Slack\SlackApi;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;

class KickUserFromSlackChannel
{
    use QueueableAction;

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function execute($userId, $channelId)
    {
        $response = $this->slackApi->conversations_kick($userId, $channelId);
        Log::info("Conversation kick response: " . print_r($response, true));
    }
}
