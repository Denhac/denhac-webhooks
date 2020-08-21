<?php

namespace App\Reactors;

use App\Customer;
use App\FeatureFlags;
use App\Google\GoogleApi;
use App\Jobs\AddCustomerToGoogleGroup;
use App\Jobs\RemoveCustomerFromGoogleGroup;
use App\StorableEvents\CustomerBecameBoardMember;
use App\StorableEvents\CustomerRemovedFromBoard;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionUpdated;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;
use YlsIdeas\FeatureFlags\Facades\Features;

final class GoogleGroupsReactor implements EventHandler
{
    private const GROUP_MEMBERS = 'members@denhac.org';
    private const GROUP_DENHAC = 'denhac@denhac.org';
    private const GROUP_BOARD = 'board@denhac.org';
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
        if($event->subscription['status'] != 'need-id-check') {
            return;
        }

        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->subscription['customer_id'])->first();

        dispatch(new AddCustomerToGoogleGroup($customer->email, self::GROUP_DENHAC));

        if(Features::accessible(FeatureFlags::NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL)) {

            dispatch(new AddCustomerToGoogleGroup($customer->email, self::GROUP_MEMBERS));
        }
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        dispatch(new AddCustomerToGoogleGroup($customer->email, self::GROUP_MEMBERS));
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if(Features::accessible(FeatureFlags::KEEP_MEMBERS_IN_SLACK_AND_EMAIL)) {
            return;
        }

        $this->googleApi->groupsForMember($customer->email)
            ->filter(function ($group) {
                return $group != 'denhac@denhac.org';
            })
            ->each(function ($group) use ($customer) {
                dispatch(new RemoveCustomerFromGoogleGroup($customer->email, $group));
            });
    }

    public function onCustomerBecameBoardMember(CustomerBecameBoardMember $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        dispatch(new AddCustomerToGoogleGroup($customer->email, self::GROUP_BOARD));
    }

    public function onCustomerRemovedFromBoard(CustomerRemovedFromBoard $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        dispatch(new RemoveCustomerFromGoogleGroup($customer->email, self::GROUP_BOARD));
    }
}
