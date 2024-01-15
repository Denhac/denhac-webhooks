<?php

namespace App\Actions\Slack;

use App\External\Slack\SlackApi;
use App\External\Slack\SlackRateLimit;
use App\Models\Customer;
use Illuminate\Support\Str;
use SlackPhp\BlockKit\Surfaces\Message;
use Spatie\QueueableAction\QueueableAction;

class SendMessage
{
    use QueueableAction;
    use SlackActionTrait;

    public string $queue = 'slack-rate-limited';

    private SlackApi $api;

    public function __construct(SlackApi $api)
    {
        $this->api = $api;
    }

    public function execute(Customer|int|string $userId, Message $message): void
    {
        if ($userId instanceof Customer) {
            $userId = $userId->slack_id;
        } elseif (is_string($userId)) {
            if (! Str::startsWith($userId, ['U', 'C'])) {  // Not a user or channel, assume customer id is a string
                $userId = intval($userId);
            }
        }

        if (is_int($userId)) {
            $userId = $this->slackIdFromGeneralId($userId);
        }

        // At this point, userId should be just a string of the slack id that we care about

        $this->api->chat->postMessage($userId, $message);
    }

    public function middleware()
    {
        return [
            SlackRateLimit::chat_postMessage(),
        ];
    }
}
