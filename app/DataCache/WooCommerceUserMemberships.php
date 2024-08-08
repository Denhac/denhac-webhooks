<?php

namespace App\DataCache;

use App\External\WooCommerce\Api\WooCommerceApi;

class WooCommerceUserMemberships extends CachedData
{
    public function __construct(
        private readonly WooCommerceApi $wooCommerceApi
    )
    {
        parent::__construct();
    }

    public function get()
    {
        return $this->cache(function () {
            return $this->wooCommerceApi->members->list($this->apiProgress('Fetching WooCommerce User Memberships'));
        });
    }
}
