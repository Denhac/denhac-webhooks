<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class SubscriptionUpdated implements ShouldBeStored
{
    public $wooId;
    public $customerId;
    public $status;

    public function __construct($wooId, $customerId, $status)
    {
        $this->wooId = $wooId;
        $this->customerId = $customerId;
        $this->status = $status;
    }
}
