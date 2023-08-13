<?php

namespace App\External\Slack\Api;


use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SlackPhp\BlockKit\Surfaces\Message;

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
