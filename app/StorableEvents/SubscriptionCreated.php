<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class SubscriptionCreated implements ShouldBeStored
{
    public function __construct()
    {
    }
}
