<?php

namespace App\Reactors;

use App\Customer;
use App\Google\GoogleApi;
use App\Jobs\AddCustomerToGoogleGroup;
use App\Jobs\RemoveCustomerFromGoogleGroup;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

final class GoogleGroupsReactor implements EventHandler
{
    private const GROUP_MEMBERS = "members@denhac.org";
    private const GROUP_DENHAC = "denhac@denhac.org";
    use HandlesEvents;

    /**
     * @var GoogleApi
     */
    private $googleApi;

    public function __construct(GoogleApi $googleApi)
    {
        $this->googleApi = $googleApi;
    }

    public function onCustomerCreated(CustomerCreated $event)
    {
        $email = $event->customer["email"];

        dispatch(new AddCustomerToGoogleGroup($email, self::GROUP_DENHAC));
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

        $this->googleApi->groupsForMember($customer->email)
            ->filter(function ($group) {
                return $group != "denhac@denhac.org";
            })
            ->each(function ($group) use ($customer) {
                dispatch(new RemoveCustomerFromGoogleGroup($customer->email, $group));
            });
    }
}
