<?php

namespace App\Reactors;

use App\Actions\Google\AddToGroup;
use App\Actions\Google\RemoveFromGroup;
use App\Models\Customer;
use App\External\Google\GoogleApi;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\Models\TrainableEquipment;
use Illuminate\Support\Collection;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

final class GoogleGroupsReactor implements EventHandler
{
    public const GROUP_MEMBERS = 'members@denhac.org';

    public const GROUP_ANNOUNCE = 'announce@denhac.org';

    public const GROUP_DENHAC = 'denhac@denhac.org';

    public const GROUP_BOARD = 'board@denhac.org';

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
        $customer = Customer::find($event->customerId);

        AddToGroup::queue()->execute($customer->email, self::GROUP_MEMBERS);
        AddToGroup::queue()->execute($customer->email, self::GROUP_ANNOUNCE);
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

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
        // TODO This function should handle _all_ emails from this person, but if they're deleted we can't access their
        // emails from WordPress and they aren't currently projected onto the customers table
        /** @var Customer $customer */
        $customer = Customer::withTrashed()  // With trashed because Customer Projector will have already soft-deleted it
            ->find($event->customerId);

        $this->googleApi->groupsForMember($customer->email)
            ->each(function ($group) use ($customer) {
                RemoveFromGroup::queue()->execute($customer->email, $group);
            });
    }

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        if ($event->membership['status'] != 'active') {
            return;
        }

        $customerId = $event->membership['customer_id'];
        $plan_id = $event->membership['plan_id'];

        /** @var Customer $customer */
        $customer = Customer::find($customerId);

        /** @var Collection $userSlackIds */
        $userEmailGroups = TrainableEquipment::select('user_email')
            ->where('user_plan_id', $plan_id)
            ->get()
            ->map(fn ($row) => $row['user_email']);

        /** @var Collection $trainerSlackIds */
        $trainerEmailGroups = TrainableEquipment::select('trainer_email')
            ->where('trainer_plan_id', $plan_id)
            ->get()
            ->map(fn ($row) => $row['trainer_email']);

        $emailGroups = collect($userEmailGroups->union($trainerEmailGroups))->unique();

        foreach ($emailGroups as $emailGroup) {
            if (is_null($emailGroup)) {
                continue;
            }

            AddToGroup::queue()->execute($customer->email, $emailGroup);
        }
    }
}
