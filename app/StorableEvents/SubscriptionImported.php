<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class SubscriptionImported implements ShouldBeStored
{
    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}
