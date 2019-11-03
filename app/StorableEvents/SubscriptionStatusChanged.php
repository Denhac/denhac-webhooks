<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class SubscriptionStatusChanged implements ShouldBeStored
{
    public $subscriptionId;
    public $oldStatus;
    public $newStatus;

    public function __construct($subscriptionId, $oldStatus, $newStatus)
    {
        $this->subscriptionId = $subscriptionId;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
