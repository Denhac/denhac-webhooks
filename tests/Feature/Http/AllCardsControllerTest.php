<?php

namespace Tests\Feature\Http;

use App\Models\Card;
use App\Models\Customer;
use App\Models\UserMembership;
use App\Models\Waiver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AllCardsControllerTest extends TestCase
{
    private string $membershipWaiverTemplateId;

    protected function setUp(): void
    {
        parent::setUp();

        Passport::actingAs($this->apiUser, ['card:manage']);

        $this->membershipWaiverTemplateId = $this->faker->uuid();
        Config::set('denhac.waiver.membership_waiver_template_id', $this->membershipWaiverTemplateId);
    }

    protected function getAllCards(): TestResponse
    {
        return $this->get('/api/all_cards')
            ->assertStatus(200);
    }

    /**
     * The access list for the given card number, across every customer in the response.
     */
    protected function accessFor(TestResponse $response, string $cardNumber): array
    {
        $cards = collect($response->json('data'))
            ->flatMap(fn ($customer) => $customer['cards'])
            ->keyBy('card_num');

        $this->assertArrayHasKey($cardNumber, $cards->all(), "Card $cardNumber was not in the response");

        return $cards[$cardNumber]['access'];
    }

    /**
     * What we currently believe WinDSX has for the given card number. This allows us to be updated by the card server
     * if there is a discrepancy.
     */
    protected function weThinkActiveFor(TestResponse $response, string $cardNumber): bool
    {
        $cards = collect($response->json('data'))
            ->flatMap(fn ($customer) => $customer['cards'])
            ->keyBy('card_num');

        $this->assertArrayHasKey($cardNumber, $cards->all(), "Card $cardNumber was not in the response");
        $this->assertArrayHasKey('we_think_active', $cards[$cardNumber], "Card $cardNumber had no we_think_active");

        return $cards[$cardNumber]['we_think_active'];
    }

    protected function signMembershipWaiver(Customer $customer): Waiver
    {
        return $this->signWaiver($customer, $this->membershipWaiverTemplateId);
    }

    protected function signWaiver(Customer $customer, string $templateId): Waiver
    {
        return Waiver::create([
            'waiver_id' => $this->faker->uuid(),
            'template_id' => $templateId,
            'template_version' => $this->faker->uuid(),
            'status' => 'accepted',
            'email' => $customer->email,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'customer_id' => $customer->id,
        ]);
    }

    #[Test] public function a_customer_with_no_cards_has_an_empty_card_list(): void
    {
        $customer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($customer);

        $this->getAllCards()
            ->assertJsonPath('data.0.cards', []);
    }

    #[Test] public function the_response_has_the_shape_the_plugin_expects(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($customer);
        /** @var Card $card */
        $card = Card::factory()->for($customer)->create();

        $this->getAllCards()
            ->assertJsonPath('data.0', [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'company' => 'DenHac',
                'cards' => [
                    [
                        'card_num' => $card->number,
                        'access' => ['denhac'],
                        'we_think_active' => false,
                    ],
                ],
                'extra' => [],
            ]);
    }

    #[Test] public function a_card_the_member_does_not_have_is_not_listed_at_all(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($customer);
        Card::factory()->for($customer)->active()->returned()->create();

        $this->getAllCards()
            ->assertJsonPath('data.0.cards', []);
    }

    /**
     * A card that should be activated but isn't yet, should still be listed when the waiver is signed and the member
     * has that card.
     */
    #[Test] public function a_member_with_a_waiver_and_the_card_is_granted_access(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($customer);
        /** @var Card $card */
        $card = Card::factory()->for($customer)->create();

        $response = $this->getAllCards();

        $this->assertEquals(['denhac'], $this->accessFor($response, $card->number));
        $this->assertFalse($this->weThinkActiveFor($response, $card->number));
    }

    #[Test] public function a_card_believed_active_for_a_non_member_is_not_granted_access(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->create();
        $this->signMembershipWaiver($customer);
        /** @var Card $card */
        $card = Card::factory()->for($customer)->active()->create();

        $response = $this->getAllCards();

        $this->assertFalse($customer->member);
        $this->assertEquals([], $this->accessFor($response, $card->number));
        $this->assertTrue($this->weThinkActiveFor($response, $card->number));
    }

    #[Test] public function a_card_believed_active_without_a_signed_waiver_is_not_granted_access(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        /** @var Card $card */
        $card = Card::factory()->for($customer)->active()->create();

        $response = $this->getAllCards();

        $this->assertEquals([], $this->accessFor($response, $card->number));
        $this->assertTrue($this->weThinkActiveFor($response, $card->number));
    }

    #[Test] public function some_other_waiver_does_not_count_as_the_membership_waiver(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->signWaiver($customer, $this->faker->uuid());
        /** @var Card $card */
        $card = Card::factory()->for($customer)->active()->create();

        $response = $this->getAllCards();

        $this->assertEquals([], $this->accessFor($response, $card->number));
        $this->assertTrue($this->weThinkActiveFor($response, $card->number));
    }

    #[Test] public function another_customers_waiver_does_not_count_as_this_customers_waiver(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        /** @var Customer $otherCustomer */
        $otherCustomer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($otherCustomer);
        /** @var Card $card */
        $card = Card::factory()->for($customer)->active()->create();

        $response = $this->getAllCards();

        $this->assertEquals([], $this->accessFor($response, $card->number));
        $this->assertTrue($this->weThinkActiveFor($response, $card->number));
    }

    #[Test] public function an_already_active_card_remains_in_the_access_list(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($customer);
        /** @var Card $card */
        $card = Card::factory()->for($customer)->active()->create();

        $response = $this->getAllCards();

        $this->assertEquals(['denhac'], $this->accessFor($response, $card->number));
        $this->assertTrue($this->weThinkActiveFor($response, $card->number));
    }

    /**
     * We don't allow multiple cards at this point in time, but enough stuff was written for it, we still make sure it
     * works in many cases.
     */
    #[Test] public function every_card_the_member_has_is_granted_access(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($customer);
        /** @var Card $activeCard */
        $activeCard = Card::factory()->for($customer)->active()->create();
        /** @var Card $inactiveCard */
        $inactiveCard = Card::factory()->for($customer)->create();

        $response = $this->getAllCards();

        $this->assertEquals(['denhac'], $this->accessFor($response, $activeCard->number));
        $this->assertTrue($this->weThinkActiveFor($response, $activeCard->number));

        $this->assertEquals(['denhac'], $this->accessFor($response, $inactiveCard->number));
        $this->assertFalse($this->weThinkActiveFor($response, $inactiveCard->number));
    }

    #[Test] public function server_room_access_is_added_for_a_member_with_that_membership(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()
            ->member()
            ->has(UserMembership::factory()->state(['plan_id' => UserMembership::SERVER_ROOM_ACCESS]), 'memberships')
            ->create();
        $this->signMembershipWaiver($customer);
        /** @var Card $card */
        $card = Card::factory()->for($customer)->create();

        $response = $this->getAllCards();

        $this->assertEquals(['denhac', 'Server Room'], $this->accessFor($response, $card->number));
        $this->assertFalse($this->weThinkActiveFor($response, $card->number));
    }

    #[Test] public function server_room_access_is_not_added_when_the_card_should_not_be_active(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()
            ->has(UserMembership::factory()->state(['plan_id' => UserMembership::SERVER_ROOM_ACCESS]), 'memberships')
            ->create();
        $this->signMembershipWaiver($customer);
        /** @var Card $card */
        $card = Card::factory()->for($customer)->active()->create();

        $this->assertFalse($customer->member);

        $response = $this->getAllCards();

        $this->assertEquals([], $this->accessFor($response, $card->number));
        $this->assertTrue($this->weThinkActiveFor($response, $card->number));
    }

    #[Test] public function the_waiver_check_does_not_add_a_query_per_customer(): void
    {
        $makeCustomer = function () {
            /** @var Customer $customer */
            $customer = Customer::factory()->member()->create();
            $this->signMembershipWaiver($customer);
            Card::factory()->for($customer)->create();
        };

        $makeCustomer();

        DB::enableQueryLog();
        $this->getAllCards();
        $withOneCustomer = count(DB::getQueryLog());

        foreach (range(1, 4) as $ignored) {
            $makeCustomer();
        }

        DB::flushQueryLog();
        $this->getAllCards();
        $withFiveCustomers = count(DB::getQueryLog());

        $this->assertSame($withOneCustomer, $withFiveCustomers);
    }
}
