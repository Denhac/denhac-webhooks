<?php

namespace App\Reactors;

use App\Aggregates\MembershipAggregate;
use App\Customer;
use App\Notifications\NewRequirementsWaiverNeeded;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\ManualBootstrapWaiverNeeded;
use App\StorableEvents\WaiverAccepted;
use App\Waiver;
use Illuminate\Support\Facades\Notification;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class WaiverReactor extends Reactor
{
    public function onWaiverAccepted(WaiverAccepted $event)
    {
        /** @var Waiver $waiver */
        $waiver = Waiver::where('waiver_id', $event->waiverEvent['content']['id'])->first();

        $customer = Customer::where('first_name', $waiver->first_name)
            ->where('last_name', $waiver->last_name)
            ->where('email', $waiver->email)
            ->first();

        if (is_null($customer)) {
            return; // No matching customer to assign this waiver to
        }

        MembershipAggregate::make($customer->woo_id)
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

    private function matchByCustomer(mixed $woo_id, string $first_name, string $last_name, string $email): void
    {
        /** @var Waiver $waiver */
        $waiver = Waiver::where('first_name', $first_name)
            ->where('last_name', $last_name)
            ->where('email', $email)
            ->first();

        if (is_null($waiver)) {
            return; // No matching waiver, meaning this customer either hasn't signed one or their info doesn't match
        }

        MembershipAggregate::make($woo_id)
            ->assignWaiver($waiver)
            ->persist();
    }

    public function onManualBootstrapWaiverNeeded(ManualBootstrapWaiverNeeded $event): void
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        $notification = new NewRequirementsWaiverNeeded($customer);
        Notification::route('mail', $customer->email)->notify($notification);
    }
}
