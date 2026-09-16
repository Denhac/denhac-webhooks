<?php

namespace Tests\Feature\Http;

use App\Aggregates\MembershipAggregate;
use App\Models\Card;
use App\Models\CardUpdateRequest;
use App\Models\Customer;
use App\StorableEvents\AccessCards\CardActivated;
use App\StorableEvents\AccessCards\CardActivatedForTheFirstTime;
use App\StorableEvents\AccessCards\CardDeactivated;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

/**
 * The single card update request status endpoint, superseded by the batch endpoint. Its body is empty: the only
 * state it can offer is the request's own type, which says what we asked for rather than what the card access
 * system did. This is being tested only because its contract is changing, just before being deleted.
 */
class CardUpdateStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Passport::actingAs($this->apiUser, ['card:manage']);

        Projectionist::withoutEventHandlers();
    }

    protected function cardNumber(): string
    {
        return Card::factory()->create()->number;
    }

    protected function postStatus(CardUpdateRequest $cardUpdateRequest, array $body = [])
    {
        return $this->postJson("/api/card_updates/{$cardUpdateRequest->id}/status", $body);
    }

    protected function cardUpdateRequest(Customer $customer, string $type, string $card): CardUpdateRequest
    {
        return CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => $type,
            'card' => $card,
        ]);
    }

    #[Test] public function an_activation_request_records_an_activation(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $this->postStatus($this->cardUpdateRequest($customer, CardUpdateRequest::ACTIVATION_TYPE, $card))
            ->assertOk();

        MembershipAggregate::fakeCustomer($customer)->assertApplied([
            new CardActivated($customer->id, $card),
            new CardActivatedForTheFirstTime($customer->id, $card),
        ]);
    }

    #[Test] public function a_deactivation_request_records_a_deactivation(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        $card = $this->cardNumber();

        $this->postStatus($this->cardUpdateRequest($customer, CardUpdateRequest::DEACTIVATION_TYPE, $card))
            ->assertOk();

        MembershipAggregate::fakeCustomer($customer)->assertApplied([
            new CardDeactivated($customer->id, $card),
        ]);
    }

    /**
     * Nothing writes a type other than the two we know about, so an unrecognized one is loud rather than treated as
     * a deactivation.
     */
    #[Test] public function an_unrecognized_type_records_nothing_and_is_reported(): void
    {
        $exceptions = Exceptions::fake();

        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();

        $this->postStatus($this->cardUpdateRequest($customer, 'lol_what_is_a_type_anyway', $this->cardNumber()))
            ->assertOk();

        MembershipAggregate::fakeCustomer($customer)->assertNothingApplied();
        $exceptions->assertReportedCount(1);
    }
}
