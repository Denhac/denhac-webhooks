<?php

namespace App\External\WooCommerce\Api;

use App\External\WooCommerce\Api\customer\CustomerApi;
use App\External\WooCommerce\Api\members\MembershipApi;
use App\External\WooCommerce\Api\subscriptions\SubscriptionsApi;
use App\External\WooCommerce\Api\webhook\WebhookApi;
use GuzzleHttp\Client;

/**
 * Class WooCommerceApi.
 *
 * @property CustomerApi customers
 * @property DenhacApi denhac
 * @property MembershipApi membership
 * @property OrdersApi orders
 * @property ProductsApi products
 * @property SubscriptionsApi subscriptions
 * @property WebhookApi webhooks
 */
class WooCommerceApi
{
    private Client $guzzleClient;

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
        return match ($name) {
            'customers' => new CustomerApi($this->guzzleClient),
            'denhac' => new DenhacApi($this->guzzleClient),
            'membership' => new MembershipApi($this->guzzleClient),
            'orders' => new OrdersApi($this->guzzleClient),
            'products' => new ProductsApi($this->guzzleClient),
            'subscriptions' => new SubscriptionsApi($this->guzzleClient),
            'webhooks' => new WebhookApi($this->guzzleClient),
            default => null,
        };
    }
}
