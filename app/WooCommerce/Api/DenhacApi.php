<?php

namespace App\WooCommerce\Api;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class DenhacApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createUserPlan($title, $authorId)
    {
        $response = $this->client->post("/wp-json/wc-denhac/v1/user_plans", [
            RequestOptions::JSON => [
                "author" => $authorId,
                "title" => $title,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}