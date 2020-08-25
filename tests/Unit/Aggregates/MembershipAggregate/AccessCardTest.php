<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;


use App\Aggregates\MembershipAggregate;
use App\StorableEvents\CardAdded;
use App\StorableEvents\CardRemoved;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionImported;
use App\StorableEvents\SubscriptionStatusChanged;
use App\StorableEvents\SubscriptionUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class AccessCardTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function access_card_is_sent_for_activation_when_membership_is_activated()
    {
        $card = '42424';
        $customer = $this->customer()
            ->access_card($card);
        $subscription = $this->subscription()
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->createSubscription($this->subscription()->status('need-id-check'))
            ->persist()
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
                new SubscriptionStatusChanged(
                    $subscription->id,
                    'need-id-check',
                    $subscription->status
                ),
            ]);
    }

    /** @test */
    public function access_card_is_sent_for_activation_when_updated_on_active_membership()
    {
        $card = '42424';
        $customer = $this->customer();
        $subscription = $this->subscription()
            ->status('active');

        $aggregate = MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->updateSubscription($subscription)
            ->persist();

        $customer->access_card($card);

        $aggregate
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
                new CardAdded($customer->id, $card),
                new CardSentForActivation($customer->id, $card),
            ]);
    }

    /** @test */
    public function updating_access_card_removes_old_cards_and_actives_new_ones()
    {
        $oldCard = '42424';
        $customer = $this->customer()
            ->access_card($oldCard);
        $subscription = $this->subscription()
            ->status('active');

        $aggregate = MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->updateSubscription($subscription)
            ->persist();

        $newCard = '53535';
        $customer->access_card($newCard);

        $aggregate
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
                new CardAdded($customer->id, $newCard),
                new CardSentForActivation($customer->id, $newCard),
                new CardRemoved($customer->id, $oldCard),
                new CardSentForDeactivation($customer->id, $oldCard),
            ]);
    }

    /** @test */
    public function multiple_cards_can_be_used_in_comma_separated_fashion()
    {
        $cards = '42424,53535';
        $customer = $this->customer()
            ->access_card($cards);
        $subscription = $this->subscription()
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->createSubscription($this->subscription()->status('need-id-check'))
            ->persist()
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, '42424'),
                new CardSentForActivation($customer->id, '53535'),
                new SubscriptionStatusChanged(
                    $subscription->id,
                    'need-id-check',
                    'active'
                ),
            ]);
    }

    /** @test */
    public function all_cards_are_deactivated_on_subscription_deactivated()
    {
        $cards = '42424,53535';
        $customer = $this->customer()
            ->access_card($cards);
        $activeSubscription = $this->subscription()
            ->status('active');
        $cancelledSubscription = $this->subscription()
            ->status('cancelled');

        MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->updateSubscription($activeSubscription)
            ->persist()
            ->updateSubscription($cancelledSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($cancelledSubscription),
                new MembershipDeactivated($customer->id),
                new CardSentForDeactivation($customer->id, '42424'),
                new CardSentForDeactivation($customer->id, '53535'),
                new SubscriptionStatusChanged(
                    $activeSubscription->id,
                    $activeSubscription->status,
                    $cancelledSubscription->status,
                ),
            ]);
    }

    /** @test */
    public function card_does_not_activate_on_active_subscription_import()
    {
        $customer = $this->customer()
            ->access_card('42424');
        $subscription = $this->subscription()
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->persist()
            ->importSubscription($subscription)
            ->assertRecorded([
                new SubscriptionImported($subscription),
                new SubscriptionStatusChanged(
                    $subscription->id,
                    null,
                    $subscription->status
                )
            ]);
    }
}
