<?php

namespace App\DataCache;

use App\External\WooCommerce\Api\WooCommerceApi;

class WooCommerceCustomers extends CachedData
{
    public function __construct(
        private readonly WooCommerceApi $wooCommerceApi
    ) {
        parent::__construct();
    }

    public function get()
    {
        return $this->cache(function () {
            return $this->wooCommerceApi->customers->list($this->apiProgress('Fetching WooCommerce Customers'));
        });
    }
}
