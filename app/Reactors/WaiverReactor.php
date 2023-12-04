<?php

namespace App\Reactors;

use App\Aggregates\MembershipAggregate;
use App\Models\Customer;
use App\Models\Waiver;
use App\Notifications\NewRequirementsWaiverNeeded;
use App\StorableEvents\Waiver\ManualBootstrapWaiverNeeded;
use App\StorableEvents\Waiver\WaiverAccepted;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerImported;
use App\StorableEvents\WooCommerce\CustomerUpdated;
use Illuminate\Support\Facades\Notification;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class WaiverReactor extends Reactor
{
    public function onWaiverAccepted(WaiverAccepted $event)
    {
        /** @var Waiver $waiver */
        $waiver = Waiver::where('waiver_id', $event->waiverEvent['content']['id'])->first();

        /** @var Customer $customer */
        $customer = Customer::where('first_name', $waiver->first_name)
            ->where('last_name', $waiver->last_name)
            ->where('email', $waiver->email)
            ->first();

        if (is_null($customer)) {
            return; // No matching customer to assign this waiver to
        }

        MembershipAggregate::make($customer->id)
            ->assignWaiver($waiver)
            ->persist();
    }

    public function onCustomerCreated(CustomerCreated $event): void
    {
        $this->matchByCustomer(
            $event->customer['id'],
            $event->customer['first_name'],
            $event->customer['last_name'],
            $event->customer['email'],
        );
    }

    public function onCustomerUpdated(CustomerUpdated $event): void
    {
        $this->matchByCustomer(
            $event->customer['id'],
            $event->customer['first_name'],
            $event->customer['last_name'],
            $event->customer['email'],
        );
    }

    public function onCustomerImported(CustomerImported $event): void
    {
        $this->matchByCustomer(
            $event->customer['id'],
            $event->customer['first_name'],
            $event->customer['last_name'],
            $event->customer['email'],
        );
    }

    private function matchByCustomer(mixed $customerId, string $firstName, string $lastName, string $email): void
    {
        /** @var Waiver $waiver */
        $waiver = Waiver::where('first_name', $firstName)
            ->where('last_name', $lastName)
            ->where('email', $email)
            ->whereNull('customer_id')
            ->first();

        if (is_null($waiver)) {
            return; // No matching waiver, meaning this customer either hasn't signed one or their info doesn't match
        }

        MembershipAggregate::make($customerId)
            ->assignWaiver($waiver)
            ->persist();
    }

    public function onManualBootstrapWaiverNeeded(ManualBootstrapWaiverNeeded $event): void
    {
        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

        $notification = new NewRequirementsWaiverNeeded($customer);
        Notification::route('mail', $customer->email)->notify($notification);
    }
}
