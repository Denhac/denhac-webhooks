<?php

namespace App\Reactors;

use App\Actions\Google\AddToGroup;
use App\Actions\Google\RemoveFromGroup;
use App\Customer;
use App\FeatureFlags;
use App\Google\GoogleApi;
use App\StorableEvents\CustomerBecameBoardMember;
use App\StorableEvents\CustomerDeleted;
use App\StorableEvents\CustomerRemovedFromBoard;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionUpdated;
use App\StorableEvents\UserMembershipCreated;
use App\UserMembership;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;
use YlsIdeas\FeatureFlags\Facades\Features;

final class GoogleGroupsReactor implements EventHandler
{
    public const GROUP_MEMBERS = 'members@denhac.org';
    public const GROUP_DENHAC = 'denhac@denhac.org';
    public const GROUP_BOARD = 'board@denhac.org';
    public const GROUP_3DP = '3dp@denhac.org';
    public const GROUP_LASER = 'laser@denhac.org';

    use HandlesEvents;

    /**
     * @var GoogleApi
     */
    private $googleApi;

    public function __construct(GoogleApi $googleApi)
    {
        $this->googleApi = $googleApi;
    }

    public function onSubscriptionUpdated(SubscriptionUpdated $event)
    {
        if ($event->subscription['status'] != 'need-id-check') {
            return;
        }

        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->subscription['customer_id'])->first();

        AddToGroup::queue()->execute($customer->email, self::GROUP_DENHAC);

        if (Features::accessible(FeatureFlags::NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL)) {
            AddToGroup::queue()->execute($customer->email, self::GROUP_MEMBERS);
        }
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        AddToGroup::queue()->execute($customer->email, self::GROUP_MEMBERS);
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (Features::accessible(FeatureFlags::KEEP_MEMBERS_IN_SLACK_AND_EMAIL)) {
            return;
        }

        $this->googleApi->groupsForMember($customer->email)
            ->filter(function ($group) {
                return $group != 'denhac@denhac.org';
            })
            ->each(function ($group) use ($customer) {
                RemoveFromGroup::queue()->execute($customer->email, $group);
            });
    }

    // Deleted customers get removed from everything
    public function onCustomerDeleted(CustomerDeleted $event)
    {
        /** @var Customer $customer */
        $customer = Customer::withTrashed()
            ->where('woo_id', $event->customerId)
            ->first();

        $this->googleApi->groupsForMember($customer->email)
            ->each(function ($group) use ($customer) {
                RemoveFromGroup::queue()->execute($customer->email, $group);
            });
    }

    public function onCustomerBecameBoardMember(CustomerBecameBoardMember $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        AddToGroup::queue()->execute($customer->email, self::GROUP_BOARD);
    }

    public function onCustomerRemovedFromBoard(CustomerRemovedFromBoard $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        RemoveFromGroup::queue()->execute($customer->email, self::GROUP_BOARD);
    }

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        if ($event->membership['status'] != 'active') {
            return;
        }

        $customerId = $event->membership['customer_id'];
        $plan_id = $event->membership['plan_id'];

        /** @var Customer $customer */
        $customer = Customer::whereWooId($customerId)->first();

        if ($plan_id == UserMembership::MEMBERSHIP_3DP_TRAINER) {
            AddToGroup::queue()->execute($customer->email, self::GROUP_3DP);
        }

        if ($plan_id == UserMembership::MEMBERSHIP_LASER_CUTTER_TRAINER) {
            AddToGroup::queue()->execute($customer->email, self::GROUP_LASER);
        }
    }
}
