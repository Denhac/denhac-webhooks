<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class SubscriptionDeleted extends ShouldBeStored
{
    public $subscription;  // Realy just the subscription id

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}
