<?php

namespace App\Reactors;

use App\Actions\AddCustomerToSlackChannel;
use App\FeatureFlags;
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
use App\StorableEvents\UserMembershipCreated;
use App\UserMembership;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;
use YlsIdeas\FeatureFlags\Facades\Features;

final class SlackReactor implements EventHandler
{
    use HandlesEvents;

    public function onSubscriptionUpdated(SubscriptionUpdated $event)
    {
        if ($event->subscription['status'] != 'need-id-check') {
            return;
        }

        if (Features::accessible(FeatureFlags::NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL)) {
            dispatch(new MakeCustomerRegularMemberInSlack($event->subscription['customer_id']));
        } else {
            dispatch(new InviteCustomerPublicOnlyMemberInSlack($event->subscription['customer_id']));
        }
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        dispatch(new MakeCustomerRegularMemberInSlack($event->customerId));
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        if (Features::accessible(FeatureFlags::KEEP_MEMBERS_IN_SLACK_AND_EMAIL)) {
            return;
        }

        dispatch(new DemoteMemberToPublicOnlyMemberInSlack($event->customerId));
    }

    public function onCustomerBecameBoardMember(CustomerBecameBoardMember $event)
    {
        app(AddCustomerToSlackChannel::class)
            ->onQueue()
            ->execute($event->customerId, 'board');
        dispatch(new AddCustomerToSlackUserGroup($event->customerId, 'theboard'));
    }

    public function onCustomerRemovedFromBoard(CustomerRemovedFromBoard $event)
    {
        dispatch(new RemoveCustomerFromSlackChannel($event->customerId, 'board'));
        dispatch(new RemoveCustomerFromSlackUserGroup($event->customerId, 'theboard'));
    }

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        if ($event->membership['status'] != 'active') {
            return;
        }

        $customerId = $event->membership['customer_id'];
        $plan_id = $event->membership['plan_id'];

        if ($plan_id == UserMembership::MEMBERSHIP_3DP_USER) {
            app(AddCustomerToSlackChannel::class)
                ->onQueue()
                ->execute($customerId, '3d-printing');
        }

        if ($plan_id == UserMembership::MEMBERSHIP_LASER_CUTTER_USER) {
            app(AddCustomerToSlackChannel::class)
                ->onQueue()
                ->execute($customerId, 'laser');
        }
    }
}
