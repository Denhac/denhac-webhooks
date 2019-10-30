<?php

namespace App\WooCommerce\Api;


use App\WooCommerce\Api\webhook\WebhookApi;
use GuzzleHttp\Client;

/**
 * Class WooCommerceApi
 * @package App\WooCommerce\Api
 * @property WebhookApi webhooks
 */
class WooCommerceApi
{
    /**
     * @var Client
     */
    private $guzzleClient;

    public function __construct($baseUrl, $consumerKey, $consumerSecret)
    {
        $this->guzzleClient = new Client([
            'base_uri' => $baseUrl,
            'auth' => [
                $consumerKey,
                $consumerSecret,
            ],
        ]);
    }

    public function __get($name)
    {
        switch ($name) {
            case "webhooks":
                return new WebhookApi($this->guzzleClient);
        }
        return null;
    }
}
