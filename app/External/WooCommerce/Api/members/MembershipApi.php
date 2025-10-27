<?php

namespace App\External\WooCommerce\Api\members;

use App\External\WooCommerce\Api\WooCommerceApiMixin;
use GuzzleHttp\Client;


/**
 * Class WooCommerceApi.
 *
 * @property PlansApi plans
 * @property MembersApi members
 */
class MembershipApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __get($name)
    {
        return match ($name) {
            'plans' => new PlansApi($this->client),
            'members' => new MembersApi($this->client),
            default => null,
        };
    }
}
