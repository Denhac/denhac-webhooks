<?php

namespace App\Projectors;

use App\Customer;
use App\StorableEvents\CustomerCreated;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class CustomerProjector implements Projector
{
    use ProjectsEvents;

    public function onCustomerCreated(CustomerCreated $event)
    {
        Customer::create([
            "woo_id" => $event->wooId,
            "email" => $event->email,
            "username" => $event->username,
            "member" => false,
        ]);
    }
}
