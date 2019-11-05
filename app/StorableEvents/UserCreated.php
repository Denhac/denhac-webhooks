<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class UserCreated implements ShouldBeStored
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
