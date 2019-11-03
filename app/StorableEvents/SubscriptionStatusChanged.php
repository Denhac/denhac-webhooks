<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class SubscriptionStatusChanged implements ShouldBeStored
{
    public $oldStatus;
    public $newStatus;

    public function __construct($oldStatus, $newStatus)
    {
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
