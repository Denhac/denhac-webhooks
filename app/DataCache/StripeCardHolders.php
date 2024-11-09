<?php

namespace App\DataCache;

use Stripe\StripeClient;

class StripeCardHolders extends CachedData
{
    public function __construct(
        private readonly StripeClient $stripeClient
    ) {
        parent::__construct();
    }

    public function get()
    {
        return $this->cache(function () {
            return collect($this->stripeClient->issuing->cardholders->all()->autoPagingIterator());
        });
    }
}
