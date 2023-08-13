<?php

namespace App\External\WooCommerce\Api;

use App\External\WooCommerce\Api\customer\CustomerApi;
use App\External\WooCommerce\Api\members\MembersApi;
use App\External\WooCommerce\Api\subscriptions\SubscriptionsApi;
use App\External\WooCommerce\Api\webhook\WebhookApi;
use GuzzleHttp\Client;

/**
 * Class WooCommerceApi.
 * @property CustomerApi customers
 * @property DenhacApi denhac
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
            case 'denhac':
                return new DenhacApi($this->guzzleClient);
            case 'members':
                return new MembersApi($this->guzzleClient);
            case 'subscriptions':
                return new SubscriptionsApi($this->guzzleClient);
            case 'webhooks':
                return new WebhookApi($this->guzzleClient);
        }
    }
}
