<?php

namespace App\Slack\Api;


use GuzzleHttp\RequestOptions;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;
use Psr\Http\Message\ResponseInterface;

class ChatApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function postMessage($conversationId, Message $message): ResponseInterface
    {
        return $this->clients->spaceBotApiClient
            ->post('https://denhac.slack.com/api/chat.postMessage', [
                RequestOptions::JSON => [
                    'channel' => $conversationId,
                    'blocks' => json_encode($message->getBlocks()),
                ],
            ]);
    }
}
