<?php

namespace App\Projectors;

use App\Customer;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerDeleted;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\IdWasChecked;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\EventHandlers\Projectors\ProjectsEvents;

final class CustomerProjector extends Projector
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
        $customer = $this->addOrGetCustomer($event->customer);

        $customer->email = $event->customer['email'];
        $customer->username = $event->customer['username'];
        $customer->first_name = $event->customer['first_name'];
        $customer->last_name = $event->customer['last_name'];
        $customer->github_username = $this->getMetadataField($event->customer, 'github_username');
        $customer->slack_id = $this->getMetadataField($event->customer, 'access_slack_id');
        $customer->birthday = $this->getMetadataFieldDate($event->customer, 'account_birthday');
        $customer->stripe_card_holder_id = $this->getMetadataField($event->customer, 'stripe_card_holder_id');
        $customer->save();
    }

    /**
     * @throws \Exception
     */
    public function onCustomerDeleted(CustomerDeleted $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (is_null($customer)) {
            report(new Exception("Failed to find customer {$event->customerId}"));

            return;
        }
        $customer->delete();
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (is_null($customer)) {
            report(new Exception("Failed to find customer {$event->customerId}"));

            return;
        }

        $customer->member = true;
        $customer->save();
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (is_null($customer)) {
            report(new Exception("Failed to find customer {$event->customerId}"));

            return;
        }

        $customer->member = false;
        $customer->save();
    }

    public function onIdWasChecked(IdWasChecked $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (is_null($customer)) {
            report(new Exception("Failed to find customer {$event->customerId}"));

            return;
        }

        $customer->id_checked = true;
        $customer->save();
    }

    /**
     * @return Customer
     */
    private function addOrGetCustomer(array $customer_json)
    {
        /** @var Customer $customerModel */
        $customerModel = Customer::whereWooId($customer_json['id'])->first();

        if (is_null($customerModel)) {
            return Customer::create([
                'woo_id' => $customer_json['id'],
                'email' => $customer_json['email'],
                'username' => $customer_json['username'],
                'first_name' => $customer_json['first_name'],
                'last_name' => $customer_json['last_name'],
                'member' => false,
                'github_username' => $this->getMetadataField($customer_json, 'github_username'),
                'slack_id' => $this->getMetadataField($customer_json, 'access_slack_id'),
                'birthday' => $this->getMetadataFieldDate($customer_json, 'account_birthday'),
                'stripe_card_holder_id' => $this->getMetadataField($customer_json, 'stripe_card_holder_id'),
            ]);
        } else {
            return $customerModel;
        }
    }

    /**
     * @param  string  $key The name of the metadata field to lookup
     * @return mixed|null
     */
    private function getMetadataField(array $customer, string $key)
    {
        return collect($customer['meta_data'])
            ->where('key', $key)
            ->first()['value'] ?? null;
    }

    private function getMetadataFieldDate(array $customer, string $key)
    {
        $string = $this->getMetadataField($customer, $key);

        if (is_null($string)) {
            return null;
        }
        try {
            return Carbon::parse($string);
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
