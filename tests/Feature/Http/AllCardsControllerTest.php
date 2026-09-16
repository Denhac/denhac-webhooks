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

    protected function card(Customer $customer, string $number, bool $active, bool $memberHasCard = true): Card
    {
        return Card::create([
            'number' => $number,
            'active' => $active,
            'member_has_card' => $memberHasCard,
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
        $this->card($customer, '373', active: false);

        $this->getAllCards()
            ->assertJsonPath('data.0', [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'company' => 'DenHac',
                'cards' => [
                    [
                        'card_num' => '373',
                        'access' => ['denhac'],
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
        $this->card($customer, '373', active: true, memberHasCard: false);

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
        $this->card($customer, '373', active: false);

        $this->assertEquals(['denhac'], $this->accessFor($this->getAllCards(), '373'));
    }

    #[Test] public function a_card_believed_active_for_a_non_member_is_not_granted_access(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->create();
        $this->signMembershipWaiver($customer);
        $this->card($customer, '373', active: true);

        $this->assertFalse($customer->member);
        $this->assertEquals([], $this->accessFor($this->getAllCards(), '373'));
    }

    #[Test] public function a_card_believed_active_without_a_signed_waiver_is_not_granted_access(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->card($customer, '373', active: true);

        $this->assertEquals([], $this->accessFor($this->getAllCards(), '373'));
    }

    #[Test] public function some_other_waiver_does_not_count_as_the_membership_waiver(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->signWaiver($customer, $this->faker->uuid());
        $this->card($customer, '373', active: true);

        $this->assertEquals([], $this->accessFor($this->getAllCards(), '373'));
    }

    #[Test] public function another_customers_waiver_does_not_count_as_this_customers_waiver(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        /** @var Customer $otherCustomer */
        $otherCustomer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($otherCustomer);
        $this->card($customer, '373', active: true);

        $this->assertEquals([], $this->accessFor($this->getAllCards(), '373'));
    }

    #[Test] public function an_already_active_card_remains_in_the_access_list(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $this->signMembershipWaiver($customer);
        $this->card($customer, '373', active: true);

        $this->assertEquals(['denhac'], $this->accessFor($this->getAllCards(), '373'));
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
        $this->card($customer, '373', active: true);
        $this->card($customer, '1974', active: false);

        $response = $this->getAllCards();

        $this->assertEquals(['denhac'], $this->accessFor($response, '373'));
        $this->assertEquals(['denhac'], $this->accessFor($response, '1974'));
    }

    #[Test] public function server_room_access_is_added_for_a_member_with_that_membership(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()
            ->member()
            ->has(UserMembership::factory()->state(['plan_id' => UserMembership::SERVER_ROOM_ACCESS]), 'memberships')
            ->create();
        $this->signMembershipWaiver($customer);
        $this->card($customer, '373', active: false);

        $this->assertEquals(['denhac', 'Server Room'], $this->accessFor($this->getAllCards(), '373'));
    }

    #[Test] public function server_room_access_is_not_added_when_the_card_should_not_be_active(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()
            ->has(UserMembership::factory()->state(['plan_id' => UserMembership::SERVER_ROOM_ACCESS]), 'memberships')
            ->create();
        $this->signMembershipWaiver($customer);
        $this->card($customer, '373', active: true);

        $this->assertFalse($customer->member);
        $this->assertEquals([], $this->accessFor($this->getAllCards(), '373'));
    }

    #[Test] public function the_waiver_check_does_not_add_a_query_per_customer(): void
    {
        $makeCustomer = function (string $cardNumber) {
            /** @var Customer $customer */
            $customer = Customer::factory()->member()->create();
            $this->signMembershipWaiver($customer);
            $this->card($customer, $cardNumber, active: false);
        };

        $makeCustomer('373');

        DB::enableQueryLog();
        $this->getAllCards();
        $withOneCustomer = count(DB::getQueryLog());

        foreach (['1974', '2216', '28991', '20943'] as $cardNumber) {
            $makeCustomer($cardNumber);
        }

        DB::flushQueryLog();
        $this->getAllCards();
        $withFiveCustomers = count(DB::getQueryLog());

        $this->assertSame($withOneCustomer, $withFiveCustomers);
    }
}
