<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class CustomerCreated implements ShouldBeStored
{
    public $wooId;
    public $email;
    public $username;

    public function __construct($wooId, $email, $username)
    {
        $this->wooId = $wooId;
        $this->email = $email;
        $this->username = $username;
    }
}
