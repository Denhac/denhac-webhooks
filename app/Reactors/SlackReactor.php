<?php

namespace App\Reactors;

use App\Customer;
use App\Jobs\AddCustomerToSlackChannel;
use App\Jobs\AddCustomerToSlackUserGroup;
use App\Jobs\MakeCustomerPublicOnlyMemberInSlack;
use App\Jobs\MakeCustomerRegularMemberInSlack;
use App\Jobs\RemoveCustomerFromSlackChannel;
use App\Jobs\RemoveCustomerFromSlackUserGroup;
use App\StorableEvents\CustomerBecameBoardMember;
use App\StorableEvents\CustomerRemovedFromBoard;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

final class SlackReactor implements EventHandler
{
    use HandlesEvents;

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        dispatch(new MakeCustomerRegularMemberInSlack($customer->woo_id));
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        dispatch(new MakeCustomerPublicOnlyMemberInSlack($customer->woo_id));
    }

    public function onCustomerBecameBoardMember(CustomerBecameBoardMember $event)
    {
        dispatch(new AddCustomerToSlackChannel($event->customerId, "board"));
        dispatch(new AddCustomerToSlackUserGroup($event->customerId, "theboard"));
    }

    public function onCustomerRemovedFromBoard(CustomerRemovedFromBoard $event)
    {
        dispatch(new RemoveCustomerFromSlackChannel($event->customerId, "board"));
        dispatch(new RemoveCustomerFromSlackUserGroup($event->customerId, "theboard"));
    }
}
