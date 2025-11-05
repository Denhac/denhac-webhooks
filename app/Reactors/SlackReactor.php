<?php

namespace App\Reactors;

use App\Actions\SetUltraRestrictedUser;
use App\Actions\Slack\AddToChannel;
use App\Actions\Slack\SendMessage;
use App\Actions\Slack\SetRegularUser;
use App\External\Slack\SlackProfileFields;
use App\Models\Card;
use App\Models\Customer;
use App\Models\TrainableEquipment;
use App\StorableEvents\AccessCards\CardActivated;
use App\StorableEvents\AccessCards\CardActivatedForTheFirstTime;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use Illuminate\Support\Collection;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Message;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

use function ltrim;

final class SlackReactor extends Reactor
{
    public function onMembershipActivated(MembershipActivated $event): void
    {
        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

        if (is_null($customer)) {
            return;
        }

        // A customer may not have a slack invite yet if they signed up and immediately got their ID check.
        if (is_null($customer->slack_id)) {
            return;
        }

        // On Slack creation, our Slack event hook will handle the profile update if the slack_id was null.
        SlackProfileFields::updateIfNeeded($customer->slack_id, []);

        // The invite will handle setting the customer as a regular user if they didn't get to this point.
        app(SetRegularUser::class)
            ->onQueue()
            ->execute($customer);

        // Let the user know their RFID card will be activated soon, since there's a delay and we don't want them to
        // send an email asking why their card doesn't work 0.2 seconds after clicking submit on the website.
        $customerFacingMessage = new Message(
            blocks: [
                Kit::section('Thanks for signing up! Your RFID card will be activated soon.'),
            ],
        );

        app(SendMessage::class)
            ->onQueue()
            ->execute($customer, $customerFacingMessage);
    }

    public function onMembershipDeactivated(MembershipDeactivated $event): void
    {
        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

        // There shouldn't be an instance where a slack id is not set on the customer at this point, however everything
        // below assumes there is a slack id. We can exit early here and have the issue checker pick off any remaining
        // issues.
        if (is_null($customer->slack_id)) {
            return;
        }

        SlackProfileFields::updateIfNeeded($customer->slack_id, []);

        app(SetUltraRestrictedUser::class)
            ->onQueue()
            ->execute($customer);
    }

    public function onUserMembershipCreated(UserMembershipCreated $event): void
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

    public function onCardActivated(CardActivated $event): void
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
        $customerFacingMessage = new Message(
            blocks: [
                Kit::section("Your RFID access card {$event->cardNumber} has been activated!"),
            ],
        );

        app(SendMessage::class)
            ->onQueue()
            ->execute($customer, $customerFacingMessage);
    }

    public function onCardActivatedForTheFirstTime(CardActivatedForTheFirstTime $event): void
    {
        /** @var Card $card */
        $card = Card::where('number', ltrim($event->cardNumber, '0'))
            ->where('customer_id', $event->customerId)
            ->first();

        if (is_null($card)) {
            return;
        }

        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

        if (is_null($customer) || is_null($customer->idWasCheckedBy)) {
            return;
        }

        $idCheckerFacingMessage = new Message(
            blocks: [
                Kit::section("Card {$event->cardNumber} activated for $customer->first_name $customer->last_name"),
            ],
        );

        app(SendMessage::class)
            ->onQueue()
            ->execute($customer->idWasCheckedBy, $idCheckerFacingMessage);
    }
}
