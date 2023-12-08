<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\CustomerImported;
use App\StorableEvents\WooCommerce\CustomerIsNoEventTestUser;
use App\StorableEvents\WooCommerce\CustomerUpdated;
use App\StorableEvents\WooCommerce\SubscriptionCreated;
use App\StorableEvents\WooCommerce\SubscriptionImported;
use App\StorableEvents\WooCommerce\SubscriptionUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class PublicMethodEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function customer_is_no_event_test_user(): void
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->customerIsNoEventTestUser()
            ->assertRecorded(new CustomerIsNoEventTestUser($customer->id));
    }

    /** @test */
    public function create_customer_call_makes_event(): void
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->createCustomer($customer)
            ->assertRecorded(new CustomerCreated($customer));
    }

    /** @test */
    public function update_customer_call_makes_event(): void
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->updateCustomer($customer)
            ->assertRecorded(new CustomerUpdated($customer));
    }

    /** @test */
    public function delete_customer_call_makes_event(): void
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->deleteCustomer($customer)
            ->assertRecorded(new CustomerDeleted($customer->id));
    }

    /** @test */
    public function import_customer_call_makes_event(): void
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->importCustomer($customer)
            ->assertRecorded(new CustomerImported($customer));
    }

    /** @test */
    public function create_subscription_call_makes_event(): void
    {
        $subscription = $this->subscription();

        MembershipAggregate::fake()
            ->createSubscription($subscription)
            ->assertRecorded([
                new SubscriptionCreated($subscription),
            ]);
    }

    /** @test */
    public function update_subscription_call_makes_event(): void
    {
        $subscription = $this->subscription();

        MembershipAggregate::fake()
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
            ]);
    }

    /** @test */
    public function create_then_update_subscription_call_makes_events(): void
    {
        $subscription = $this->subscription();

        MembershipAggregate::fake()
            ->createSubscription($subscription)
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionCreated($subscription),
                new SubscriptionUpdated($subscription),
            ]);
    }

    /** @test */
    public function update_then_create_subscription_call_makes_events(): void
    {
        $subscription = $this->subscription();

        MembershipAggregate::fake()
            ->updateSubscription($subscription)
            ->createSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
                new SubscriptionCreated($subscription),
            ]);
    }

    /** @test */
    public function import_subscription_call_makes_event(): void
    {
        $subscription = $this->subscription();

        MembershipAggregate::fake()
            ->importSubscription($subscription)
            ->assertRecorded([
                new SubscriptionImported($subscription),
            ]);
    }
}
