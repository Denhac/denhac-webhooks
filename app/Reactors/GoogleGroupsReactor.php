<?php

namespace App\Reactors;

use App\Customer;
use App\Google\GoogleApi;
use App\StorableEvents\MembershipActivated;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

final class GoogleGroupsReactor implements EventHandler
{
    use HandlesEvents;

    /**
     * @var GoogleApi
     */
    private $googleApi;

    public function __construct(GoogleApi $googleApi)
    {
        $this->googleApi = $googleApi;
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        $this->googleApi->group("members@denhac.org")->add($customer->email);
        $this->googleApi->group("denhac@denhac.org")->add($customer->email);
    }
}
