<?php

namespace App\Projectors;

use App\Customer;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
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

    public function onCustomerUpdated(CustomerUpdated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customer["id"])->first();

        $customer->email = $customer["email"];
        $customer->username = $customer["username"];
        $customer->first_name = $customer["first_name"];
        $customer->last_name = $customer["last_name"];
        $customer->save();
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        $customer->member = true;
        $customer->save();
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        $customer->member = false;
        $customer->save();
    }
}
