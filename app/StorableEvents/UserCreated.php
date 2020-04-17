<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class UserCreated implements ShouldBeStored
{
    public $name;
    public $api_token;

    public function __construct($name, $api_token)
    {
        $this->name = $name;
        $this->api_token = $api_token;
    }
}
