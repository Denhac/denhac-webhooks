<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class SubscriptionImported extends ShouldBeStored
{
    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}
