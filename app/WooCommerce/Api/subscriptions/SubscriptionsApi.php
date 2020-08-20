<?php

namespace App\WooCommerce\Api\subscriptions;

use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApiMixin;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class SubscriptionsApi
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
        return $this->getWithPaging('/wp-json/wc/v1/subscriptions');
    }

    /**
     * @param $woo_id
     * @param array $json
     * @return Collection
     * @throws ApiCallFailed
     */
    public function update($woo_id, array $json)
    {
        $response = $this->client->post("/wp-json/wc/v1/subscriptions/$woo_id", [
            RequestOptions::JSON => $json,
        ]);

        return $this->jsonOrError($response);
    }
}
