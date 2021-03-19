<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\ADUpdateRequest;
use App\Aggregates\MembershipAggregate;
use App\CardUpdateRequest;
use App\StorableEvents\CustomerIsNoEventTestUser;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class NoEventUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function create_customer_call_does_not_make_event()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given(new CustomerIsNoEventTestUser($customer->id))
            ->createCustomer($customer)
            ->assertNothingRecorded();
    }

    /** @test */
    public function update_customer_call_does_not_make_event()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given(new CustomerIsNoEventTestUser($customer->id))
            ->updateCustomer($customer)
            ->assertNothingRecorded();
    }

    /** @test */
    public function delete_customer_call_does_not_make_event()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given(new CustomerIsNoEventTestUser($customer->id))
            ->deleteCustomer($customer)
            ->assertNothingRecorded();
    }

    /** @test */
    public function import_customer_call_does_not_make_event()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given(new CustomerIsNoEventTestUser($customer->id))
            ->importCustomer($customer)
            ->assertNothingRecorded();
    }

    /** @test */
    public function create_subscription_call_does_not_make_event()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given(new CustomerIsNoEventTestUser($customer->id))
            ->createSubscription($this->subscription())
            ->assertNothingRecorded();
    }

    /** @test */
    public function update_subscription_call_does_not_make_event()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given(new CustomerIsNoEventTestUser($customer->id))
            ->updateSubscription($this->subscription())
            ->assertNothingRecorded();
    }

    /** @test */
    public function import_subscription_call_does_not_make_event()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given(new CustomerIsNoEventTestUser($customer->id))
            ->importSubscription($this->subscription())
            ->assertNothingRecorded();
    }

    /** @test */
    public function card_update_request_does_not_make_event()
    {
        $customer = $this->customer();

        $cardUpdateRequest = CardUpdateRequest::create([
            'customer_id' => $customer->id,
            'type' => CardUpdateRequest::DEACTIVATION_TYPE,
            'card' => '42424',
        ]);

        MembershipAggregate::fakeCustomer($customer->id)
            ->given(new CustomerIsNoEventTestUser($customer->id))
            ->updateCardStatus($cardUpdateRequest, CardUpdateRequest::STATUS_SUCCESS)
            ->assertNothingRecorded();
    }
}
