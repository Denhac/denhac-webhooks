<?php

namespace App\Actions\Slack;

use App\Slack\SlackApi;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;
use Spatie\QueueableAction\QueueableAction;

class SendMessage
{
    use QueueableAction;
    use SlackActionTrait;

    private SlackApi $api;

    public function __construct(SlackApi $api)
    {
        $this->api = $api;
    }

    public function execute($userId, Message $message): void
    {
        $userId = $this->slackIdFromGeneralId($userId);

        $this->api->chat_postMessage($userId, $message);
    }
}
