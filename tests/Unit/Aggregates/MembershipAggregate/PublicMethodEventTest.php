<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerDeleted;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerIsNoEventTestUser;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionImported;
use App\StorableEvents\SubscriptionStatusChanged;
use App\StorableEvents\SubscriptionUpdated;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PublicMethodEventTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    /** @test */
    public function customerIsNoEventTestUser()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->customerIsNoEventTestUser()
            ->assertRecorded(new CustomerIsNoEventTestUser($customer->id));
    }

    /** @test */
    public function createCustomerCallMakesEvent()
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->createCustomer($customer)
            ->assertRecorded(new CustomerCreated($customer));
    }

    /** @test */
    public function updateCustomerCallMakesEvent()
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->updateCustomer($customer)
            ->assertRecorded(new CustomerUpdated($customer));
    }

    /** @test */
    public function deleteCustomerCallMakesEvent()
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->deleteCustomer($customer)
            ->assertRecorded(new CustomerDeleted($customer));
    }

    /** @test */
    public function importCustomerCallMakesEvent()
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->importCustomer($customer)
            ->assertRecorded(new CustomerImported($customer));
    }

    /** @test */
    public function createSubscriptionCallMakesEvent()
    {
        $subscription = $this->subscription();

        MembershipAggregate::fake()
            ->createSubscription($subscription)
            ->assertRecorded([
                new SubscriptionCreated($subscription),
                new SubscriptionStatusChanged(
                    $subscription->id,
                    null,
                    $subscription->status
                )
            ]);
    }

    /** @test */
    public function updateSubscriptionCallMakesEvent()
    {
        $subscription = $this->subscription();

        MembershipAggregate::fake()
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
                new SubscriptionStatusChanged(
                    $subscription->id,
                    null,
                    $subscription->status
                )
            ]);
    }

    /** @test */
    public function importSubscriptionCallMakesEvent()
    {
        $subscription = $this->subscription();

        MembershipAggregate::fake()
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
