<?php

namespace App\WooCommerce\Api\customer;


use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApiMixin;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class CustomerApi
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
        return $this->getWithPaging("/wp-json/wc/v3/customers", [
            RequestOptions::QUERY => [
                "role" => "all"
            ],
        ]);
    }
}
