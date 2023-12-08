<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\Models\CardUpdateRequest;
use App\Models\UserMembership;
use App\Models\Waiver;
use App\StorableEvents\AccessCards\CardActivated;
use App\StorableEvents\AccessCards\CardActivatedForTheFirstTime;
use App\StorableEvents\AccessCards\CardAdded;
use App\StorableEvents\AccessCards\CardDeactivated;
use App\StorableEvents\AccessCards\CardRemoved;
use App\StorableEvents\AccessCards\CardSentForActivation;
use App\StorableEvents\AccessCards\CardSentForDeactivation;
use App\StorableEvents\AccessCards\CardStatusUpdated;
use App\StorableEvents\Membership\IdWasChecked;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\Waiver\ManualBootstrapWaiverNeeded;
use App\StorableEvents\Waiver\WaiverAssignedToCustomer;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerUpdated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class AccessCardTest extends TestCase
{
    private string $memberWaiverTemplateId;

    private string $memberWaiverTemplateVersion;

    private Waiver $membershipWaiver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->memberWaiverTemplateId = $this->faker->uuid();
        $this->memberWaiverTemplateVersion = $this->faker->uuid();

        Config::set('denhac.waiver.membership_waiver_template_id', $this->memberWaiverTemplateId);
        Config::set('denhac.waiver.membership_waiver_template_version', $this->memberWaiverTemplateVersion);

        /** @var Waiver $waiver */
        $this->membershipWaiver = Waiver::create([
            'waiver_id' => $this->faker->uuid(),
            'template_id' => $this->memberWaiverTemplateId,
            'template_version' => $this->memberWaiverTemplateVersion,
            'status' => 'accepted',
            'email' => $this->faker->email(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ]);

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function access_card_is_not_sent_for_activation_when_membership_is_activated_if_waiver_is_not_signed(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $pausedUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new UserMembershipCreated($pausedUserMembership),
                new IdWasChecked($customer->id),
            ])
            ->updateUserMembership($activeUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
            ])
            ->assertNotRecorded(CardSentForActivation::class);
    }

    /** @test */
    public function access_card_is_sent_for_activation_when_membership_is_activated_if_waiver_is_signed(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $pausedUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new UserMembershipCreated($pausedUserMembership),
                new IdWasChecked($customer->id),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
            ])
            ->updateUserMembership($activeUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
            ]);
    }

    /** @test */
    public function access_card_is_sent_for_activation_when_waiver_is_signed_on_membership_activate(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new UserMembershipCreated($activeUserMembership),
                new IdWasChecked($customer->id),
                new MembershipActivated($customer->id),
            ])
            ->assignWaiver($this->membershipWaiver)
            ->assertRecorded([
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
                new CardSentForActivation($customer->id, $card),
            ]);
    }

    /** @test */
    public function waiver_must_be_correct_template_id(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $pausedUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        /** @var Waiver $waiver */
        $waiver = Waiver::create([
            'waiver_id' => $this->faker->uuid(),
            'template_id' => $this->faker->uuid(),  // Incorrect template ID
            'template_version' => $this->faker->uuid(),
            'status' => 'accepted',
            'email' => $this->faker->email(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ]);

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new UserMembershipCreated($pausedUserMembership),
                new IdWasChecked($customer->id),
                new WaiverAssignedToCustomer($waiver->waiver_id, $customer->id),
            ])
            ->updateUserMembership($activeUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
            ])
            ->assertNotRecorded(CardSentForActivation::class);
    }

    /** @test */
    public function manual_bootstrapping_deactivates_cards(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new UserMembershipCreated($activeUserMembership),
                new IdWasChecked($customer->id),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
                new CardActivated($customer->id, $card),
            ])
            ->bootstrapWaiverRequirement()
            ->assertRecorded([
                new ManualBootstrapWaiverNeeded($customer->id),
                new CardSentForDeactivation($customer->id, $card),
            ]);
    }

    /** @test */
    public function manual_bootstrapping_cannot_occur_twice(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new UserMembershipCreated($activeUserMembership),
                new IdWasChecked($customer->id),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
                new CardActivated($customer->id, $card),
                new ManualBootstrapWaiverNeeded($customer->id),
                new CardSentForDeactivation($customer->id, $card),
            ])
            ->bootstrapWaiverRequirement()
            ->assertNothingRecorded();
    }

    /** @test */
    public function manual_bootstrapping_does_nothing_if_waiver_is_assigned(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new UserMembershipCreated($activeUserMembership),
                new IdWasChecked($customer->id),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
                new CardActivated($customer->id, $card),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
            ])
            ->bootstrapWaiverRequirement()
            ->assertNothingRecorded();
    }

    /**
     * @test
     * We only do updated here since it's impossible to assign a waiver to a customer until we get the customer created event.
     */
    public function customer_updated_after_user_membership_created_does_not_activate_cards_without_waiver(): void
    {
        $card = '42424';
        $customer = $this->customer()->id_was_checked();
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new IdWasChecked($customer->id),
                new UserMembershipCreated($activeUserMembership),
                new MembershipActivated($customer->id),
            ])
            ->updateCustomer($customer->access_card($card))
            ->assertRecorded([
                new CustomerUpdated($customer),
                new CardAdded($customer->id, $card),
            ]);
    }

    /**
     * @test
     * We only do updated here since it's impossible to assign a waiver to a customer until we get the customer created event.
     */
    public function customer_updated_after_user_membership_created_activates_cards_with_waiver(): void
    {
        $card = '42424';
        $customer = $this->customer()->id_was_checked();
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new IdWasChecked($customer->id),
                new UserMembershipCreated($activeUserMembership),
                new MembershipActivated($customer->id),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
            ])
            ->updateCustomer($customer->access_card($card))
            ->assertRecorded([
                new CustomerUpdated($customer),
                new CardAdded($customer->id, $card),
                new CardSentForActivation($customer->id, $card),
            ]);
    }

    /** @test */
    public function former_member_becoming_a_member_again_sends_card_to_be_activated(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $pausedUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new UserMembershipCreated($pausedUserMembership),
                new IdWasChecked($customer->id),
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
                new CardSentForActivation($customer->id, $card),
                new CardActivated($customer->id, $card),
                new UserMembershipUpdated($pausedUserMembership),
                new MembershipDeactivated($customer->id),
                new CardSentForDeactivation($customer->id, $card),
                new CardDeactivated($customer->id, $card),
            ])
            ->updateUserMembership($activeUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
            ])
            ->assertNotRecorded(CardActivatedForTheFirstTime::class);
    }

    /** @test */
    public function updating_access_card_removes_old_cards_and_actives_new_ones(): void
    {
        $oldCard = '42424';
        $newCard = '53535';
        $customer = $this->customer()->access_card($oldCard);

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardAdded($customer->id, $oldCard),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
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
    public function multiple_cards_can_be_used_in_comma_separated_fashion(): void
    {
        $cards = '42424,53535';
        $customer = $this->customer()->access_card($cards);

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new MembershipActivated($customer->id),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
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
    public function card_is_not_sent_for_activation_if_it_has_already_been_sent(): void
    {
        $card = '42424';
        $customer = $this->customer()
            ->access_card($card);

        $agg = MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new MembershipActivated($customer->id),
                new CardAdded($customer->id, $card),
                new CardSentForActivation($customer->id, $card),
            ]);
        $agg->activateCardsNeedingActivation();
        $agg->assertNotRecorded(CardSentForActivation::class);
    }

    /** @test */
    public function card_is_not_added_if_card_number_is_null(): void
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
    public function update_card_status_records_activated_card_on_success(): void
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
                new CardActivatedForTheFirstTime($customer->id, $card),
            ]);
    }

    /** @test
     * @throws Exception
     */
    public function update_card_status_records_deactivated_card_on_success(): void
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
    public function unknown_update_request_type_throws_exception(): void
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
    public function non_successful_card_update_request_status_throws_exception(): void
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

    /** @test */
    public function card_added_adds_to_cards_on_account(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);

        $fakeAggregateRoot = MembershipAggregate::fakeCustomer($customer);
        /** @var MembershipAggregate $agg */
        $agg = $fakeAggregateRoot->aggregateRoot();

        $this->assertTrue($agg->cardsOnAccount->isEmpty());
        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertTrue($agg->cardsEverActivated->isEmpty());

        $fakeAggregateRoot
            ->createCustomer($customer)
            ->assertRecorded([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
            ]);

        $this->assertEquals(1, $agg->cardsOnAccount->count());
        $this->assertTrue($agg->cardsOnAccount->has($card));

        $this->assertEquals(1, $agg->cardsNeedingActivation->count());
        $this->assertTrue($agg->cardsNeedingActivation->has($card));

        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertTrue($agg->cardsEverActivated->isEmpty());
    }

    /** @test */
    public function card_sent_for_activation_adds_to_cards_sent_for_activation(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        $fakeAggregateRoot = MembershipAggregate::fakeCustomer($customer);
        /** @var MembershipAggregate $agg */
        $agg = $fakeAggregateRoot->aggregateRoot();

        $this->assertTrue($agg->cardsOnAccount->isEmpty());
        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertTrue($agg->cardsEverActivated->isEmpty());

        $fakeAggregateRoot
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new IdWasChecked($customer->id),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
            ])
            ->updateUserMembership($activeUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
            ]);

        $this->assertEquals(1, $agg->cardsOnAccount->count());
        $this->assertTrue($agg->cardsOnAccount->has($card));

        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());

        $this->assertEquals(1, $agg->cardsSentForActivation->count());
        $this->assertTrue($agg->cardsSentForActivation->has($card));
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertTrue($agg->cardsEverActivated->isEmpty());
    }

    /** @test */
    public function card_activated_removes_from_other_lists(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        /** @var CardUpdateRequest $cardUpdateRequest */
        $cardUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'card' => $card,
        ]);

        $fakeAggregateRoot = MembershipAggregate::fakeCustomer($customer);
        /** @var MembershipAggregate $agg */
        $agg = $fakeAggregateRoot->aggregateRoot();

        $this->assertTrue($agg->cardsOnAccount->isEmpty());
        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertTrue($agg->cardsEverActivated->isEmpty());

        $fakeAggregateRoot
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new IdWasChecked($customer->id),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
            ])
            ->updateCardStatus($cardUpdateRequest, CardUpdateRequest::STATUS_SUCCESS)
            ->assertRecorded([
                new CardStatusUpdated(
                    $cardUpdateRequest->type,
                    $cardUpdateRequest->customer_id,
                    $cardUpdateRequest->card,
                ),
                new CardActivated($customer->id, $card),
                new CardActivatedForTheFirstTime($customer->id, $card),
            ]);

        $this->assertEquals(1, $agg->cardsOnAccount->count());
        $this->assertTrue($agg->cardsOnAccount->has($card));
        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertEquals(1, $agg->cardsEverActivated->count());
        $this->assertTrue($agg->cardsEverActivated->has($card));
    }

    /** @test */
    public function card_sent_for_deactivation_adds_to_cards_sent_for_deactivation(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $cancelledUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('cancelled');
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        /** @var CardUpdateRequest $activationUpdateRequest */
        $activationUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'card' => $card,
        ]);

        $fakeAggregateRoot = MembershipAggregate::fakeCustomer($customer);
        /** @var MembershipAggregate $agg */
        $agg = $fakeAggregateRoot->aggregateRoot();

        $this->assertTrue($agg->cardsOnAccount->isEmpty());
        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertTrue($agg->cardsEverActivated->isEmpty());

        $fakeAggregateRoot
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new IdWasChecked($customer->id),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
                new CardStatusUpdated(
                    $activationUpdateRequest->type,
                    $activationUpdateRequest->customer_id,
                    $activationUpdateRequest->card,
                ),
                new CardActivated($customer->id, $card),
            ])
            ->updateUserMembership($cancelledUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($cancelledUserMembership),
                new MembershipDeactivated($customer->id),
                new CardSentForDeactivation($customer->id, $card),
            ]);

        $this->assertEquals(1, $agg->cardsOnAccount->count());
        $this->assertTrue($agg->cardsOnAccount->has($card));
        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertEquals(1, $agg->cardsSentForDeactivation->count());
        $this->assertTrue($agg->cardsSentForDeactivation->has($card));
        $this->assertEquals(1, $agg->cardsEverActivated->count());
        $this->assertTrue($agg->cardsEverActivated->has($card));
    }

    /** @test */
    public function card_deactivated_removes_from_other_lists(): void
    {
        $card = '42424';
        $customer = $this->customer()->access_card($card);
        $activeUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        /** @var CardUpdateRequest $activationUpdateRequest */
        $activationUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'card' => $card,
        ]);

        /** @var CardUpdateRequest $deactivationUpdateRequest */
        $deactivationUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::DEACTIVATION_TYPE,
            'card' => $card,
        ]);

        $fakeAggregateRoot = MembershipAggregate::fakeCustomer($customer);
        /** @var MembershipAggregate $agg */
        $agg = $fakeAggregateRoot->aggregateRoot();

        $this->assertTrue($agg->cardsOnAccount->isEmpty());
        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertTrue($agg->cardsEverActivated->isEmpty());

        $fakeAggregateRoot
            ->given([
                new CustomerCreated($customer),
                new CardAdded($customer->id, $card),
                new IdWasChecked($customer->id),
                new WaiverAssignedToCustomer($this->membershipWaiver->waiver_id, $customer->id),
                new UserMembershipUpdated($activeUserMembership),
                new MembershipActivated($customer->id),
                new CardSentForActivation($customer->id, $card),
                new CardStatusUpdated(
                    $activationUpdateRequest->type,
                    $activationUpdateRequest->customer_id,
                    $activationUpdateRequest->card,
                ),
                new CardActivated($customer->id, $card),
            ])
            ->updateCardStatus($deactivationUpdateRequest, CardUpdateRequest::STATUS_SUCCESS)
            ->assertRecorded([
                new CardStatusUpdated(
                    $deactivationUpdateRequest->type,
                    $deactivationUpdateRequest->customer_id,
                    $deactivationUpdateRequest->card,
                ),
                new CardDeactivated($customer->id, $card),
            ]);

        $this->assertEquals(1, $agg->cardsOnAccount->count());
        $this->assertTrue($agg->cardsOnAccount->has($card));
        $this->assertTrue($agg->cardsNeedingActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForActivation->isEmpty());
        $this->assertTrue($agg->cardsSentForDeactivation->isEmpty());
        $this->assertEquals(1, $agg->cardsEverActivated->count());
        $this->assertTrue($agg->cardsEverActivated->has($card));
    }
}
