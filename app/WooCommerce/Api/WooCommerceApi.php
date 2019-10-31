<?php

namespace App\WooCommerce\Api;


use App\WooCommerce\Api\customer\CustomerApi;
use App\WooCommerce\Api\subscriptions\SubscriptionsApi;
use App\WooCommerce\Api\webhook\WebhookApi;
use GuzzleHttp\Client;

/**
 * Class WooCommerceApi
 * @package App\WooCommerce\Api
 * @property WebhookApi webhooks
 * @property CustomerApi customers
 * @property SubscriptionsApi subscriptions
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
            case "customers":
                return new CustomerApi($this->guzzleClient);
            case "subscriptions":
                return new SubscriptionsApi($this->guzzleClient);
        }
        return null;
    }
}
