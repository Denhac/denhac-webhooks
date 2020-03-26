<?php

namespace App\Projectors;

use App\Customer;
use App\PaypalBasedMember;
use App\StorableEvents\CustomerCapabilitiesImported;
use App\StorableEvents\CustomerCapabilitiesUpdated;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionImported;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class CustomerProjector implements Projector
{
    use ProjectsEvents;

    public function onStartingEventReplay()
    {
        Customer::truncate();
    }

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

        $customer->email = $customer['email'];
        $customer->username = $customer['username'];
        $customer->first_name = $customer['first_name'];
        $customer->last_name = $customer['last_name'];
        $customer->github_username = $this->getMetadataField($event->customer, 'github_username');
        $customer->slack_id = $this->getMetadataField($event->customer, 'access_slack_id');
        $customer->save();
    }

    public function onSubscriptionImported(SubscriptionImported $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->subscription['customer_id'])->first();

        if ($event->subscription['status'] == 'active') {
            $customer->member = true;
            $customer->save();
        }
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

    public function onCustomerCapabilitiesImported(CustomerCapabilitiesImported $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        $customer->capabilities = $event->capabilities;
        $customer->save();
    }

    public function onCustomerCapabilitiesUpdated(CustomerCapabilitiesUpdated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        $customer->capabilities = $event->capabilities;
        $customer->save();
    }

    /**
     * @param array $customer
     * @return mixed
     */
    private function addOrGetCustomer($customer)
    {
        /** @var Customer $customer */
        $customerModel = Customer::whereWooId($customer['id'])->first();

        if (is_null($customerModel)) {
            return Customer::create([
                'woo_id' => $customer['id'],
                'email' => $customer['email'],
                'username' => $customer['username'],
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'member' => false,
                'github_username' => $this->getMetadataField($customer, 'github_username'),
                'slack_id' => $this->getMetadataField($customer, 'access_slack_id'),
            ]);
        } else {
            return $customerModel;
        }
    }

    /**
     * @param array $customer
     * @param string $key The name of the metadata field to lookup
     * @return mixed|null
     */
    private function getMetadataField($customer, $key)
    {
        return collect($customer['meta_data'])
                ->where('key', $key)
                ->first()['value'] ?? null;
    }
}
