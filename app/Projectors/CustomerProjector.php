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
        $customer = $event->customer;

        Customer::create([
            "woo_id" => $customer["id"],
            "email" => $customer["email"],
            "username" => $customer["username"],
            "first_name" => $customer["first_name"],
            "last_name" => $customer["last_name"],
            "member" => false,
        ]);
    }

    // TODO Customer updated
}
