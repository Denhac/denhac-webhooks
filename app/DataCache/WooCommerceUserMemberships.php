<?php

namespace App\DataCache;

use App\External\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\Collection;

class WooCommerceUserMemberships extends CachedData
{
    public function __construct(
        private readonly WooCommerceApi $wooCommerceApi
    ) {
        parent::__construct();
    }

    public function get(): Collection
    {
        return $this->cache(function () {
            return $this->wooCommerceApi->membership->members->list($this->apiProgress('Fetching WooCommerce User Memberships'));
        });
    }
}
