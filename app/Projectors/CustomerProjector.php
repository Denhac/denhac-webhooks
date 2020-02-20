<?php

namespace App\Projectors;

use App\Customer;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class CustomerProjector implements Projector
{
    use ProjectsEvents;

    public function onCustomerImported(CustomerImported $event)
    {
        $this->addOrGetCustomer($event->customer);
    }

    public function onCustomerCreated(CustomerCreated $event)
    {
        $this->addOrGetCustomer($event->customer);
    }

    public function onCustomerUpdated(CustomerUpdated $event)
    {
        /** @var Customer $customer */
        $customer = $this->addOrGetCustomer($event->customer);

        $customer->email = $customer["email"];
        $customer->username = $customer["username"];
        $customer->first_name = $customer["first_name"];
        $customer->last_name = $customer["last_name"];
        $metadata = collect($customer["meta_data"]);
        $customer->github_username = $metadata
            ->where('key', 'github_username')
            ->first()['value'];
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

    /**
     * @param $customer
     * @return mixed
     */
    private function addOrGetCustomer($customer)
    {
        /** @var Customer $customer */
        $customerModel = Customer::whereWooId($customer["id"])->first();

        if(is_null($customerModel)) {
            return Customer::create([
                "woo_id" => $customer["id"],
                "email" => $customer["email"],
                "username" => $customer["username"],
                "first_name" => $customer["first_name"],
                "last_name" => $customer["last_name"],
                "member" => false,
            ]);
        } else {
            return $customerModel;
        }
    }
}
