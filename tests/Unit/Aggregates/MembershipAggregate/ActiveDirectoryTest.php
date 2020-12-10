<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;


use App\Aggregates\MembershipAggregate;
use App\StorableEvents\ADUserToBeDisabled;
use App\StorableEvents\ADUserToBeEnabled;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class ActiveDirectoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function ad_is_ready_to_enable_when_membership_is_activated()
    {
        $customer = $this->customer();
        $subscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('need-id-check')),
            ])
            ->updateSubscription($subscription)
            ->assertRecorded([
                new SubscriptionUpdated($subscription),
                new MembershipActivated($customer->id),
                new ADUserToBeEnabled($customer->id),
            ]);
    }

    /** @test */
    public function ad_is_ready_to_disable_when_membership_is_deactivated()
    {
        $customer = $this->customer();
        $cancelledSubscription = $this->subscription()->status('cancelled');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionUpdated($this->subscription()->status('active')),
            ])
            ->updateSubscription($cancelledSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($cancelledSubscription),
                new MembershipDeactivated($customer->id),
                new ADUserToBeDisabled($customer->id),
            ]);
    }
}
