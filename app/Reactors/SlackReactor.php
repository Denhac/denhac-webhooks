<?php

namespace App\Reactors;

use App\Customer;
use App\FeatureFlags;
use App\Jobs\AddCustomerToSlackChannel;
use App\Jobs\AddCustomerToSlackUserGroup;
use App\Jobs\DemoteMemberToPublicOnlyMemberInSlack;
use App\Jobs\InviteCustomerPublicOnlyMemberInSlack;
use App\Jobs\MakeCustomerRegularMemberInSlack;
use App\Jobs\RemoveCustomerFromSlackChannel;
use App\Jobs\RemoveCustomerFromSlackUserGroup;
use App\StorableEvents\CustomerBecameBoardMember;
use App\StorableEvents\CustomerRemovedFromBoard;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionUpdated;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;
use YlsIdeas\FeatureFlags\Facades\Features;

final class SlackReactor implements EventHandler
{
    use HandlesEvents;

    public function onSubscriptionUpdated(SubscriptionUpdated $event)
    {
        if($event->subscription['status'] != 'need-id-check') {
            return;
        }

        if(Features::accessible(FeatureFlags::NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL)) {
            dispatch(new MakeCustomerRegularMemberInSlack($event->subscription['customer_id']));
        } else {
            dispatch(new InviteCustomerPublicOnlyMemberInSlack($event->subscription['customer_id']));
        }
    }

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

        if(Features::accessible(FeatureFlags::KEEP_MEMBERS_IN_SLACK_AND_EMAIL)) {
            return;
        }

        dispatch(new DemoteMemberToPublicOnlyMemberInSlack($customer->woo_id));
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
