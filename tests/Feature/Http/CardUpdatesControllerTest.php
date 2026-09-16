<?php

namespace Tests\Feature\Http;

use App\Aggregates\MembershipAggregate;
use App\Models\Card;
use App\Models\CardUpdateRequest;
use App\Models\Customer;
use App\Projectors\CardProjector;
use App\Projectors\CardUpdateRequestProjector;
use App\StorableEvents\AccessCards\CardActivated;
use App\StorableEvents\AccessCards\CardActivatedForTheFirstTime;
use App\StorableEvents\AccessCards\CardDeactivated;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

/**
 * The batch status endpoint. Bulk sync acts with no card_update_requests row to report against, so an update id is
 * optional here. The piecemeal path uses this endpoint too, which is why an id is still accepted.
 *
 * Invalid entries are ignored and reported, allowing valid entries not to be blocked while a human resolves the issue.
 */
class CardUpdatesControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Passport::actingAs($this->apiUser, ['card:manage']);

        Projectionist::withoutEventHandlers();
    }

    protected function postCardUpdates(array $updates): TestResponse
    {
        return $this->postJson('/api/card_updates', ['updates' => $updates]);
    }

    protected function cardNumber(): string
    {
        return Card::factory()->create()->number;
    }

    protected function assertApplied(Customer $customer, array $events): void
    {
        MembershipAggregate::fakeCustomer($customer)->assertApplied($events);
    }

    protected function assertNothingApplied(Customer $customer): void
    {
        MembershipAggregate::fakeCustomer($customer)->assertNothingApplied();
    }

    #[Test] public function an_update_with_no_id_records_an_activation(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => $card, 'active' => true],
        ])->assertOk();

        $this->assertApplied($customer, [
            new CardActivated($customer->id, $card),
            new CardActivatedForTheFirstTime($customer->id, $card),
        ]);
    }

    #[Test] public function an_update_with_no_id_records_a_deactivation(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => $card, 'active' => false],
        ])->assertOk();

        $this->assertApplied($customer, [
            new CardDeactivated($customer->id, $card),
        ]);
    }

    #[Test] public function an_update_with_no_id_corrects_the_card_we_believed_inactive(): void
    {
        Projectionist::addProjector(CardProjector::class);

        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        /** @var Card $card */
        $card = Card::factory()->for($customer)->create();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => $card->number, 'active' => true],
        ])->assertOk();

        $this->assertTrue($card->fresh()->active);
    }

    #[Test] public function an_update_with_no_id_corrects_the_card_we_believed_active(): void
    {
        Projectionist::addProjector(CardProjector::class);

        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        /** @var Card $card */
        $card = Card::factory()->for($customer)->active()->create();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => $card->number, 'active' => false],
        ])->assertOk();

        $this->assertFalse($card->fresh()->active);
    }

    #[Test] public function an_update_id_does_not_change_which_events_are_recorded(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $request = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'card' => $card,
        ]);

        $this->postCardUpdates([
            ['id' => $request->id, 'woo_id' => $customer->id, 'card' => $card, 'active' => true],
        ])->assertOk();

        $this->assertApplied($customer, [
            new CardActivated($customer->id, $card),
            new CardActivatedForTheFirstTime($customer->id, $card),
        ]);
    }

    #[Test] public function reporting_an_activation_drains_the_pending_activation_request(): void
    {
        Projectionist::addProjector(CardUpdateRequestProjector::class);

        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $request = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'card' => $card,
        ]);

        $this->postCardUpdates([
            ['id' => $request->id, 'woo_id' => $customer->id, 'card' => $card, 'active' => true],
        ])->assertOk();

        $this->assertNull($request->fresh());
    }

    /**
     * CardUpdateRequestProjector matches on customer, card and type, not on the id, so an id-less report drains a
     * card update request that was asking for the same thing.
     */
    #[Test] public function a_report_with_no_id_still_drains_a_matching_pending_request(): void
    {
        Projectionist::addProjector(CardUpdateRequestProjector::class);

        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $request = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'card' => $card,
        ]);

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => $card, 'active' => true],
        ])->assertOk();

        $this->assertNull($request->fresh());
    }

    #[Test] public function an_update_id_that_does_not_exist_is_not_an_error(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $this->postCardUpdates([
            ['id' => 99999, 'woo_id' => $customer->id, 'card' => $card, 'active' => true],
        ])->assertOk();

        $this->assertApplied($customer, [
            new CardActivated($customer->id, $card),
            new CardActivatedForTheFirstTime($customer->id, $card),
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------
    // Batching.
    // -----------------------------------------------------------------------------------------------------------

    #[Test] public function every_update_in_the_batch_is_recorded(): void
    {
        /** @var Customer $first */
        $first = Customer::factory()->member()->create();
        /** @var Customer $second */
        $second = Customer::factory()->member()->create();
        $firstCard = $this->cardNumber();
        $secondCard = $this->cardNumber();

        $this->postCardUpdates([
            ['woo_id' => $first->id, 'card' => $firstCard, 'active' => true],
            ['woo_id' => $second->id, 'card' => $secondCard, 'active' => false],
        ])->assertOk();

        $this->assertApplied($first, [
            new CardActivated($first->id, $firstCard),
            new CardActivatedForTheFirstTime($first->id, $firstCard),
        ]);
        $this->assertApplied($second, [
            new CardDeactivated($second->id, $secondCard),
        ]);
    }

    #[Test] public function two_updates_for_the_same_customer_in_one_batch_are_both_recorded(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $returnedCard = $this->cardNumber();
        $newCard = $this->cardNumber();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => $returnedCard, 'active' => false],
            ['woo_id' => $customer->id, 'card' => $newCard, 'active' => true],
        ])->assertOk();

        $this->assertApplied($customer, [
            new CardDeactivated($customer->id, $returnedCard),
            new CardActivated($customer->id, $newCard),
            new CardActivatedForTheFirstTime($customer->id, $newCard),
        ]);
    }

    #[Test] public function an_empty_batch_is_accepted_and_records_nothing(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();

        $this->postCardUpdates([])->assertOk();

        $this->assertNothingApplied($customer);
    }

    #[Test] public function a_card_number_sent_as_an_integer_is_recorded_as_a_string(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => (int) $card, 'active' => true],
        ])->assertOk();

        $this->assertApplied($customer, [
            new CardActivated($customer->id, $card),
            new CardActivatedForTheFirstTime($customer->id, $card),
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------
    // Bad entries.
    // -----------------------------------------------------------------------------------------------------------

    public static function malformedUpdates(): array
    {
        return [
            'no card' => [fn (int $wooId, string $card) => ['woo_id' => $wooId, 'active' => true]],
            'no customer' => [fn (int $wooId, string $card) => ['card' => $card, 'active' => true]],
            'no resulting state' => [fn (int $wooId, string $card) => ['woo_id' => $wooId, 'card' => $card]],
            'state is not a boolean' => [fn (int $wooId, string $card) => ['woo_id' => $wooId, 'card' => $card, 'active' => 'yes please']],
            'customer is not an integer' => [fn (int $wooId, string $card) => ['woo_id' => 'Holmes', 'card' => $card, 'active' => true]],
            'customer id of zero' => [fn (int $wooId, string $card) => ['woo_id' => 0, 'card' => $card, 'active' => true]],
        ];
    }

    #[Test]
    #[DataProvider('malformedUpdates')]
    public function a_malformed_update_records_nothing(callable $update): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();

        $this->postCardUpdates([$update($customer->id, $this->cardNumber())])->assertOk();

        $this->assertNothingApplied($customer);
    }

    #[Test]
    #[DataProvider('malformedUpdates')]
    public function a_malformed_update_does_not_hold_up_the_rest_of_the_batch(callable $update): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        /** @var Customer $other */
        $other = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $this->postCardUpdates([
            $update($customer->id, $this->cardNumber()),
            ['woo_id' => $other->id, 'card' => $card, 'active' => true],
        ])->assertOk();

        $this->assertApplied($other, [
            new CardActivated($other->id, $card),
            new CardActivatedForTheFirstTime($other->id, $card),
        ]);
    }

    #[Test] public function a_malformed_update_is_reported(): void
    {
        $exceptions = Exceptions::fake();

        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'active' => true],
            ['woo_id' => $customer->id, 'card' => $this->cardNumber(), 'active' => true],
        ])->assertOk();

        $exceptions->assertReportedCount(1);
    }

    #[Test] public function a_well_formed_batch_reports_nothing(): void
    {
        $exceptions = Exceptions::fake();

        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => $this->cardNumber(), 'active' => true],
        ])->assertOk();

        $exceptions->assertNothingReported();
    }

    /**
     * Entries are skipped one at a time, but a body with no updates in it cannot be acted on at all.
     */
    #[Test] public function a_request_with_no_updates_key_is_rejected(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();

        $this->postJson('/api/card_updates')->assertStatus(422);

        $this->assertNothingApplied($customer);
    }

    #[Test] public function a_request_whose_updates_are_not_a_list_is_rejected(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();

        $this->postJson('/api/card_updates', ['updates' => 'nope'])->assertStatus(422);

        $this->assertNothingApplied($customer);
    }

    #[Test] public function the_endpoint_requires_the_card_manage_scope(): void
    {
        Passport::actingAs($this->apiUser, ['slack:invite']);

        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();

        $this->postCardUpdates([
            ['woo_id' => $customer->id, 'card' => $this->cardNumber(), 'active' => true],
        ])->assertForbidden();

        $this->assertNothingApplied($customer);
    }
}
