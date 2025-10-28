<?php

namespace App\External\WooCommerce\Api\members;

use App\External\ApiProgress;
use App\External\WooCommerce\Api\ApiCallFailed;
use App\External\WooCommerce\Api\WooCommerceApiMixin;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class PlansApi
{
    use WooCommerceApiMixin;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws ApiCallFailed
     */
    public function list(?ApiProgress $progress = null): Collection
    {
        return $this->getWithPaging('/wp-json/wc/v3/memberships/plans', [
            RequestOptions::QUERY => [],
        ], $progress);
    }
}
