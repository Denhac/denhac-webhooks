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

        $wooId = $customer["id"];
        $email = $customer["email"];
        $username = $customer["username"];

        #TODO Add First and Last Name
        Customer::create([
            "woo_id" => $wooId,
            "email" => $email,
            "username" => $username,
            "member" => false,
        ]);
    }
}
