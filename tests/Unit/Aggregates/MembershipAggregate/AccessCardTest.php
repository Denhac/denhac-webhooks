<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;


use App\Aggregates\MembershipAggregate;
use App\StorableEvents\CardAdded;
use App\StorableEvents\CardRemoved;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionImported;
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
        $customer = $this->customer();
        $subscription = $this->subscription()
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('need-id-check')),
                new CardAdded($customer->id, $card),

            ])
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
            ]);
    }

    /** @test */
    public function access_card_is_sent_for_activation_when_updated_on_active_membership()
    {
        $card = '42424';
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
            ])
            ->updateCustomer($customer->access_card($card))
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
        $newCard = '53535';
        $customer = $this->customer()
            ->access_card($oldCard);

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardAdded($customer->id, $oldCard),
                new CardSentForActivation($customer->id, $oldCard),
            ])
            ->updateCustomer($customer->access_card($newCard))
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

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new MembershipActivated($customer->id),
            ])
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
                new CardAdded($customer->id, '42424'),
                new CardSentForActivation($customer->id, '42424'),
                new CardAdded($customer->id, '53535'),
                new CardSentForActivation($customer->id, '53535'),
            ]);
    }

    /** @test */
    public function all_cards_are_deactivated_on_subscription_deactivated()
    {
        $customer = $this->customer();
        $cancelledSubscription = $this->subscription()
            ->status('cancelled');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, '42424'),
                new CardAdded($customer->id, '53535'),
                new SubscriptionUpdated($this->subscription()->status('active')),
            ])
            ->updateSubscription($cancelledSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($cancelledSubscription),
                new MembershipDeactivated($customer->id),
                new CardSentForDeactivation($customer->id, '42424'),
                new CardSentForDeactivation($customer->id, '53535'),
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
            ->given([
                new CustomerCreated($customer),
            ])
            ->persist()
            ->importSubscription($subscription)
            ->assertRecorded([
                new SubscriptionImported($subscription),
            ]);
    }
}
