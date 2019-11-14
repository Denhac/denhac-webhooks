<?php

namespace App\Slack;


use GuzzleHttp\Client;

class SlackApi
{
    /**
     * @var Client
     */
    private $guzzleClient;

    public function __construct($apiToken)
    {
        $this->guzzleClient = new Client([
            'headers' => [
                'Authorization' => "Bearer $apiToken",
            ],
        ]);
    }

    public function users_list()
    {
        # TODO Make this handle errors/pagination
        return collect(json_decode($this->guzzleClient
            ->get("https://denhac.slack.com/api/users.list")
            ->getBody(), true)["members"]);
    }
}
