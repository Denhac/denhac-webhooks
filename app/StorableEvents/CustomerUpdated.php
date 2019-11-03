<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class CustomerUpdated implements ShouldBeStored
{
    public function __construct()
    {
    }
}
