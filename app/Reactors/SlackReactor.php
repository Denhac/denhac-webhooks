<?php

namespace App\Reactors;

use App\Actions\Slack\AddToChannel;
use App\Models\Customer;
use App\External\Slack\SlackProfileFields;
use App\Jobs\DemoteMemberToPublicOnlyMemberInSlack;
use App\Jobs\InviteCustomerNeedIdCheckOnlyMemberInSlack;
use App\Jobs\MakeCustomerRegularMemberInSlack;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\Models\TrainableEquipment;
use Illuminate\Support\Collection;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

final class SlackReactor implements EventHandler
{
    use HandlesEvents;

    public function onCustomerCreated(CustomerCreated $event)
    {
        // TODO Technically this should be specific to a new customer who is signing up, vs something like a manual user
        dispatch(new InviteCustomerNeedIdCheckOnlyMemberInSlack($event->customer['id']));
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        dispatch(new MakeCustomerRegularMemberInSlack($event->customerId));
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (! is_null($customer)) {
            SlackProfileFields::updateIfNeeded($customer->slack_id, []);
        }

        dispatch(new DemoteMemberToPublicOnlyMemberInSlack($event->customerId));
    }

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        if ($event->membership['status'] != 'active') {
            return;
        }

        $customerId = $event->membership['customer_id'];
        $plan_id = $event->membership['plan_id'];

        /** @var Collection $userSlackIds */
        $userSlackIds = TrainableEquipment::select('user_slack_id')
            ->where('user_plan_id', $plan_id)
            ->get()
            ->map(fn ($row) => $row['user_slack_id']);

        /** @var Collection $trainerSlackIds */
        $trainerSlackIds = TrainableEquipment::select('trainer_slack_id')
            ->where('trainer_plan_id', $plan_id)
            ->get()
            ->map(fn ($row) => $row['trainer_slack_id']);

        $slackIds = collect($userSlackIds->union($trainerSlackIds))->unique();

        foreach ($slackIds as $slackId) {
            if (is_null($slackId)) {
                continue;
            }

            AddToChannel::queue()->execute($customerId, $slackId);
        }
    }
}
