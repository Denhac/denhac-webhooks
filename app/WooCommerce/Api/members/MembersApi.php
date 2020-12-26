<?php

namespace App\WooCommerce\Api\members;

use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApiMixin;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class MembersApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Collection
     * @throws ApiCallFailed
     */
    public function list()
    {
        return $this->getWithPaging('/wp-json/wc/v3/memberships/members', [
            RequestOptions::QUERY => [
                'role' => 'all',
            ],
        ]);
    }

    /**
     * @param $woo_id
     * @param $plan_id
     * @return Collection
     * @throws ApiCallFailed
     */
    public function addMembership($woo_id, $plan_id)
    {
        $response = $this->client->post('/wp-json/wc/v3/memberships/members', [
            RequestOptions::JSON => [
                'customer_id' => $woo_id,
                'plan_id' => $plan_id,
            ],
        ]);

        return $this->jsonOrError($response);
    }

    /**
     * @param $woo_id
     * @return Collection
     * @throws ApiCallFailed
     */
    public function get($woo_id)
    {
        $response = $this->client->get('/wp-json/wc/v3/memberships/members', [
            RequestOptions::QUERY => [
                'customer' => $woo_id,
            ],
        ]);

        return $this->jsonOrError($response);
    }
}
