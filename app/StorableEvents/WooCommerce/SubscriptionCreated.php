<?php

namespace App\StorableEvents\WooCommerce;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class SubscriptionCreated extends ShouldBeStored
{
    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}
