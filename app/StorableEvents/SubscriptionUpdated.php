<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class SubscriptionUpdated implements ShouldBeStored
{
    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}
