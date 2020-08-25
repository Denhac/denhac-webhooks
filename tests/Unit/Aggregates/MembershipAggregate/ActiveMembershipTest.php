<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;


use App\Aggregates\MembershipAggregate;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class ActiveMembershipTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function going_from_need_id_check_to_active_subscription_activates_membership()
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('need-id-check')),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function going_from_id_was_check_to_active_subscription_activates_membership()
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('id-was-checked')),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function going_from_active_to_cancelled_subscription_deactivates_membership()
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('cancelled');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('active')),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new MembershipDeactivated($customer->id),
            ]);
    }

    /** @test */
    public function going_from_active_to_suspended_payment_subscription_deactivates_membership()
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('suspended-payment');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('active')),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new MembershipDeactivated($customer->id),
            ]);
    }

    /** @test */
    public function going_from_active_to_suspended_manual_subscription_deactivates_membership()
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('suspended-manual');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('active')),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new MembershipDeactivated($customer->id),
            ]);
    }
}
