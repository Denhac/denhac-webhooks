<?php

namespace App\External\WooCommerce\Api\subscriptions;

use App\External\ApiProgress;
use App\External\WooCommerce\Api\ApiCallFailed;
use App\External\WooCommerce\Api\WooCommerceApiMixin;
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
     * @throws ApiCallFailed
     */
    public function list(ApiProgress $progress = null): Collection
    {
        return $this->getWithPaging('/wp-json/wc/v1/subscriptions', [], $progress);
    }

    /**
     *
     * @throws ApiCallFailed
     */
    public function update($woo_id, array $json): Collection
    {
        $response = $this->client->post("/wp-json/wc/v1/subscriptions/$woo_id", [
            RequestOptions::JSON => $json,
        ]);

        return $this->jsonOrError($response);
    }

    /**
     *
     * @throws ApiCallFailed
     */
    public function get($woo_id): Collection
    {
        $response = $this->client->get("/wp-json/wc/v1/subscriptions/$woo_id");

        return $this->jsonOrError($response);
    }
}
