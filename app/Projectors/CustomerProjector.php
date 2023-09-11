<?php

namespace App\Projectors;

use App\Models\Customer;
use App\StorableEvents\Membership\IdWasChecked;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\CustomerImported;
use App\StorableEvents\WooCommerce\CustomerUpdated;
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
        $this->addOrUpdateCustomerFromJson($event->customer);
    }

    public function onCustomerCreated(CustomerCreated $event)
    {
        $this->addOrUpdateCustomerFromJson($event->customer);
    }

    public function onCustomerUpdated(CustomerUpdated $event)
    {
        $this->addOrUpdateCustomerFromJson($event->customer);
    }

    /**
     * @throws \Exception
     */
    public function onCustomerDeleted(CustomerDeleted $event)
    {
        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

        if (is_null($customer)) {
            report(new Exception("Failed to find customer {$event->customerId}"));

            return;
        }
        $customer->delete();
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

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
        $customer = Customer::find($event->customerId);

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
        $customer = Customer::find($event->customerId);

        if (is_null($customer)) {
            report(new Exception("Failed to find customer {$event->customerId}"));

            return;
        }

        $customer->id_checked = true;
        $customer->save();
    }

    /**
     * @param array $customer_json
     * @return Customer
     */
    private function addOrUpdateCustomerFromJson(array $customer_json): Customer
    {
        /** @var Customer $customerModel */
        $customerModel = Customer::find($customer_json['id']);

        if (is_null($customerModel)) {
            /** @var Customer $customerModel */
            $customerModel = Customer::make();
            $customerModel->member = false;
        }

        $customerModel->id = $customer_json['id'];
        $customerModel->email = $customer_json['email'];
        $customerModel->username = $customer_json['username'];
        $customerModel->first_name = $customer_json['first_name'];
        $customerModel->last_name = $customer_json['last_name'];
        $customerModel->github_username = $this->getMetadataField($customer_json, 'github_username');
        $customerModel->slack_id = $this->getMetadataField($customer_json, 'access_slack_id');
        $customerModel->birthday = $this->getMetadataFieldDate($customer_json, 'account_birthday');
        $customerModel->stripe_card_holder_id = $this->getMetadataField($customer_json, 'stripe_card_holder_id');
        $customerModel->save();

        return $customerModel;
    }

    /**
     * @param string $key The name of the metadata field to lookup
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
