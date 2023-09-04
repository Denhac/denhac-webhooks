<?php

namespace App\External\WooCommerce\Api;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class ProductsApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Collection
     *
     * @throws ApiCallFailed
     */
    public function get($woo_id)
    {
        $response = $this->client->get("/wp-json/wc/v3/products/$woo_id");

        return $this->jsonOrError($response);
    }
}
