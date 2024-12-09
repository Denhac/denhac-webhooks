<?php

namespace App\StorableEvents\Stripe;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class IssuingAuthorization extends ShouldBeStored
{
    public array $stripeEvent;

    public function __construct($stripeEvent)
    {
        $this->stripeEvent = $stripeEvent;
    }
}
