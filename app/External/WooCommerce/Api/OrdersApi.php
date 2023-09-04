<?php

namespace App\External\WooCommerce\Api;

use App\External\ApiProgress;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class OrdersApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws ApiCallFailed
     */
    public function list(array $query = [], ApiProgress $progress = null): Collection
    {
        return $this->getWithPaging('/wp-json/wc/v3/orders', [
            RequestOptions::QUERY => array_merge_recursive($query, [
                'role' => 'all',
            ]),
        ], $progress);
    }
}
