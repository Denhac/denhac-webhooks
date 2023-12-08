<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\SubscriptionCreated;
use App\StorableEvents\WooCommerce\SubscriptionUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

/**
 * We used to activate/de-activate memberships based on subscriptions. For household/group memberships, this meant we
 * had to have a fake subscription for the non-paying members of the group. Now, the subscription is just the billing
 * aspect and user memberships per customer manage whether that person is a member or not. It does away with custom
 * subscription statuses and a whole bunch of weird logic around subscription statuses.
 *
 * With that background in mind, we still want to keep up with subscriptions because it can tell us things like whether
 * they're a student, part of a group membership, or just on their own. We still track subscription changes, but we need
 * to make sure they do not affect whether they're an active member or not, which is most of these tests.
 */
class SubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function going_from_paused_to_active_subscription_does_nothing(): void
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionCreated($this->subscription()->status('paused')),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function going_from_null_to_active_subscription_does_not_activate_membership(): void
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function going_from_active_to_cancelled_subscription_does_not_deactivate_membership(): void
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('cancelled');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('active')),
                new MembershipActivated($customer->id),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function going_from_active_to_suspended_payment_subscription_does_not_deactivate_membership(): void
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('suspended-payment');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('active')),
                new MembershipActivated($customer->id),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function going_from_active_to_suspended_manual_subscription_does_not_deactivate_membership(): void
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('suspended-manual');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('active')),
                new MembershipActivated($customer->id),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function going_from_active_to_active_subscription_does_nothing(): void
    {
        $customer = $this->customer();

        $subscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($subscription),
                new MembershipActivated($customer->id),
            ])
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
            ]);
    }
}
