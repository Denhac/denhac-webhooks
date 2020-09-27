<?php

namespace App\WooCommerce\Api;

use App\WooCommerce\Api\customer\CustomerApi;
use App\WooCommerce\Api\members\MembersApi;
use App\WooCommerce\Api\subscriptions\SubscriptionsApi;
use App\WooCommerce\Api\webhook\WebhookApi;
use GuzzleHttp\Client;

/**
 * Class WooCommerceApi.
 * @property CustomerApi customers
 * @property MembersApi members
 * @property SubscriptionsApi subscriptions
 * @property WebhookApi webhooks
 */
class WooCommerceApi
{
    /**
     * @var Client
     */
    private $guzzleClient;

    public function __construct()
    {
        $this->guzzleClient = new Client([
            'base_uri' => config('denhac.url'),
            'auth' => [
                config('denhac.rest.key'),
                config('denhac.rest.secret'),
            ],
        ]);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'customers':
                return new CustomerApi($this->guzzleClient);
            case 'members':
                return new MembersApi($this->guzzleClient);
            case 'subscriptions':
                return new SubscriptionsApi($this->guzzleClient);
            case 'webhooks':
                return new WebhookApi($this->guzzleClient);
        }
    }
}
