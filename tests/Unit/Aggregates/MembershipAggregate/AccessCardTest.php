<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\CardUpdateRequest;
use App\StorableEvents\CardActivated;
use App\StorableEvents\CardAdded;
use App\StorableEvents\CardDeactivated;
use App\StorableEvents\CardRemoved;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use App\StorableEvents\CardStatusUpdated;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionUpdated;
use Exception;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class AccessCardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function access_card_is_not_sent_for_activation_when_subscription_is_activated()
    {
        $card = '42424';
        $customer = $this->customer();
        $subscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('need-id-check')),
                new CardAdded($customer->id, $card),
            ])
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
            ]);
    }

    /** @test */
    public function updating_access_card_removes_old_cards_and_actives_new_ones()
    {
        $oldCard = '42424';
        $newCard = '53535';
        $customer = $this->customer()->access_card($oldCard);

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
        $customer = $this->customer()->access_card($cards);

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
    public function all_cards_are_not_sent_for_deactivation_on_subscription_deactivated()
    {
        $customer = $this->customer();
        $cancelledSubscription = $this->subscription()->status('cancelled');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, '42424'),
                new CardAdded($customer->id, '53535'),
                new SubscriptionUpdated($this->subscription()->status('active')),
                new MembershipActivated($customer->id),
            ])
            ->updateSubscription($cancelledSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($cancelledSubscription),
            ]);
    }

    /** @test */
    public function card_is_not_sent_for_activation_if_it_has_already_been_sent()
    {
        $card = '42424';
        $customer = $this->customer()
            ->access_card($card);

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardAdded($customer->id, $card),
                new CardSentForActivation($customer->id, $card),
            ])
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
            ]);
    }

    /** @test */
    public function card_is_not_added_if_card_number_is_null()
    {
        $customer = $this->customer()
            ->access_card(null);

        MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->assertRecorded([
                new CustomerCreated($customer),
            ]);
    }

    /** @test
     * @throws Exception
     */
    public function update_card_status_records_activated_card_on_success()
    {
        $card = '42424';
        $customer = $this->customer();

        $cardUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'card' => $card,
        ]);

        MembershipAggregate::fakeCustomer($customer->id)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardAdded($customer->id, $card),
                new CardSentForActivation($customer->id, $card),
            ])
            ->updateCardStatus($cardUpdateRequest, CardUpdateRequest::STATUS_SUCCESS)
            ->assertRecorded([
                new CardStatusUpdated(
                    CardUpdateRequest::ACTIVATION_TYPE,
                    $customer->id,
                    $card
                ),
                new CardActivated($customer->id, $card),
            ]);
    }

    /** @test
     * @throws Exception
     */
    public function update_card_status_records_deactivated_card_on_success()
    {
        $card = '42424';
        $customer = $this->customer();

        $cardUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::DEACTIVATION_TYPE,
            'card' => $card,
        ]);

        MembershipAggregate::fakeCustomer($customer->id)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardRemoved($customer->id, $card),
                new CardSentForDeactivation($customer->id, $card),
            ])
            ->updateCardStatus($cardUpdateRequest, CardUpdateRequest::STATUS_SUCCESS)
            ->assertRecorded([
                new CardStatusUpdated(
                    CardUpdateRequest::DEACTIVATION_TYPE,
                    $customer->id,
                    $card
                ),
                new CardDeactivated($customer->id, $card),
            ]);
    }

    /** @test */
    public function former_member_becoming_a_member_through_their_subscription_does_nothing()
    {
        $card = '42424';
        $customer = $this->customer();
        $subscription = $this->subscription()->status('need-id-check');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardAdded($customer->id, $card),
                new CardSentForActivation($customer->id, $card),
                new CardActivated($customer->id, $card),
                new MembershipDeactivated($customer->id),
                new CardSentForDeactivation($customer->id, $card),
                new CardDeactivated($customer->id, $card),
                new SubscriptionCreated($subscription),
            ])
            ->updateSubscription($subscription->status('active'))
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
            ]);
    }

    /** @test */
    public function unknown_update_request_type_throws_exception()
    {
        $fakeType = 'lol_what_is_a_type_anyway';
        $card = '42424';
        $customer = $this->customer();

        $cardUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => $fakeType,
            'card' => $card,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Card update request type wasn't one of the expected values: $fakeType");

        MembershipAggregate::fakeCustomer($customer->id)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardRemoved($customer->id, $card),
                new CardSentForDeactivation($customer->id, $card),
            ])
            ->updateCardStatus($cardUpdateRequest, CardUpdateRequest::STATUS_SUCCESS);
    }

    /** @test */
    public function non_successful_card_update_request_status_throws_exception()
    {
        $fakeStatus = 'fake_status_goes_here';
        $card = '42424';
        $customer = $this->customer();

        $cardUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'card' => $card,
        ]);

        $this->expectException(Exception::class);
        $message = "Card update (Customer: {$customer->id}, Card: $card, Type: enable) not successful";
        $this->expectExceptionMessage($message);

        MembershipAggregate::fakeCustomer($customer->id)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardRemoved($customer->id, $card),
                new CardSentForDeactivation($customer->id, $card),
            ])
            ->updateCardStatus($cardUpdateRequest, $fakeStatus);
    }
}
