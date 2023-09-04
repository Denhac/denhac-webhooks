<?php

namespace App\External\WooCommerce\Api;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class DenhacApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createUserPlan($title, $authorId)
    {
        $response = $this->client->post('/wp-json/wc-denhac/v1/user_plans', [
            RequestOptions::JSON => [
                'author' => $authorId,
                'title' => $title,
            ],
        ]);

        Log::info('Create User Plan');
        Log::info(json_decode($response->getBody(), true));

        return json_decode($response->getBody(), true);
    }
}
