<?php

namespace App\Reactors;

use App\Actions\Slack\AddToChannel;
use App\Actions\Slack\SendMessage;
use App\External\Slack\SlackApi;
use App\Models\Card;
use App\Models\Customer;
use App\External\Slack\SlackProfileFields;
use App\Jobs\DemoteMemberToPublicOnlyMemberInSlack;
use App\Jobs\InviteCustomerNeedIdCheckOnlyMemberInSlack;
use App\Jobs\MakeCustomerRegularMemberInSlack;
use App\StorableEvents\AccessCards\CardActivated;
use App\StorableEvents\AccessCards\CardDeactivated;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\Models\TrainableEquipment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use SlackPhp\BlockKit\Surfaces\Message;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;
use function ltrim;

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
        $customer = Customer::find($event->customerId);

        if (!is_null($customer)) {
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
            ->map(fn($row) => $row['user_slack_id']);

        /** @var Collection $trainerSlackIds */
        $trainerSlackIds = TrainableEquipment::select('trainer_slack_id')
            ->where('trainer_plan_id', $plan_id)
            ->get()
            ->map(fn($row) => $row['trainer_slack_id']);

        $slackIds = collect($userSlackIds->union($trainerSlackIds))->unique();

        foreach ($slackIds as $slackId) {
            if (is_null($slackId)) {
                continue;
            }

            AddToChannel::queue()->execute($customerId, $slackId);
        }
    }

    public function onCardActivated(CardActivated $event)
    {
        /** @var Card $card */
        $card = Card::where('number', ltrim($event->cardNumber, '0'))
            ->where('customer_id', $event->wooCustomerId)
            ->first();

        if (is_null($card)) {
            return;
        }

        /** @var Customer $customer */
        $customer = Customer::find($event->wooCustomerId);

        if (is_null($customer)) {
            return;
        }

        // Let the customer know their card is activated
        $customerFacingMessage = Message::new()
            ->inChannel()
            ->text("Your RFID access card {$event->cardNumber} has been activated!");

        app(SendMessage::class)
            ->onQueue()
            ->execute($customer, $customerFacingMessage);

        /** @var Customer $idChecker */
        $idChecker = $customer->id_was_checked_by;
        // We only want to notify the id checker if this card was created within the last hour, ie during the id check
        $shouldNotifyIdChecker = $card->created_at >= Carbon::now()->subHour();
        if (! is_null($idChecker) && $shouldNotifyIdChecker) {
            $idCheckerFacingMessage = Message::new()
                ->inChannel()
                ->text("Card {$event->cardNumber} activated for {$customer->first_name} {$customer->last_name}");

            app(SendMessage::class)
                ->onQueue()
                ->execute($customer, $idCheckerFacingMessage);
        }
    }

}
