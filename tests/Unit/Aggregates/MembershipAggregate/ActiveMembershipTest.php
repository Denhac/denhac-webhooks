<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\IdWasChecked;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionUpdated;
use App\StorableEvents\UserMembershipCreated;
use App\StorableEvents\UserMembershipUpdated;
use App\UserMembership;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class ActiveMembershipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function going_from_paused_to_active_subscription_does_nothing()
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
    public function going_from_need_id_check_to_active_subscription_does_not_activate_membership()
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionCreated($this->subscription()->status('need-id-check')),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function going_from_id_was_check_to_active_subscription_does_not_activate_membership()
    {
        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new SubscriptionCreated($this->subscription()->status('id-was-checked')),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function going_from_null_to_active_subscription_does_not_activate_membership()
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
    public function going_from_active_to_cancelled_subscription_does_not_deactivate_membership()
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
    public function going_from_active_to_suspended_payment_subscription_does_not_deactivate_membership()
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
    public function going_from_active_to_active_subscription_does_nothing()
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

    /** @test */
    public function going_from_active_to_suspended_manual_subscription_does_not_deactivate_membership()
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
    public function canceling_one_subscription_with_another_still_active_does_not_deactivate_membership()
    {
        $subscriptionA = $this->subscription()->id(1)->status('active');
        $subscriptionB = $this->subscription()->id(2)->status('active');
        $customer = $this->customer();

        $aggregate = MembershipAggregate::fakeCustomer($customer)
            ->given([
                new SubscriptionCreated($subscriptionA),
                new SubscriptionCreated($subscriptionB),
            ]);

        $subscriptionB->status('cancelled');

        $aggregate
            ->updateSubscription($subscriptionB)
            ->assertRecorded([
                new SubscriptionUpdated($subscriptionB),
            ]);
    }

    /** @test */
    public function user_membership_from_paused_to_active_activates_membership_with_id_check()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new IdWasChecked($customer->id),
            ])
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_with_different_plan_to_active_does_not_activate_membership()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_3DP_USER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_3DP_USER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new IdWasChecked($customer->id),
            ])
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
            ]);
    }

    /** @test */
    public function user_membership_with_different_plan_to_active_does_not_activate_membership_on_id_check()
    {
        $customer = $this->customer();

        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_3DP_USER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($newUserMembership),
            ])
            ->updateCustomer($customer->id_was_checked())
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_from_paused_to_active_does_not_activate_membership_without_id_check()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
            ])
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
            ]);
    }

    /** @test */
    public function user_membership_when_active_emits_membership_activated_if_id_is_checked()
    {
        $customer = $this->customer();

        $userMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($userMembership),
            ])
            ->updateCustomer($customer->id_was_checked())
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_from_active_to_cancelled_deactivates_membership()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('cancelled');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new MembershipActivated($customer->id),
            ])
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
                new MembershipDeactivated($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_from_active_to_expired_deactivates_membership()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('expired');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new MembershipActivated($customer->id),
            ])
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
                new MembershipDeactivated($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_from_active_to_active_does_nothing()
    {
        $customer = $this->customer();

        $userMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($userMembership),
                new MembershipActivated($customer->id),
            ])
            ->updateUserMembership($userMembership)
            ->assertRecorded([
                new UserMembershipUpdated($userMembership),
            ]);
    }

    /** @test */
    public function active_subscription_then_active_user_membership_with_id_check_activates_once()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new IdWasChecked($customer->id),
                new UserMembershipCreated($oldUserMembership),
                new SubscriptionCreated($this->subscription()->status('need-id-check')),
            ])
            ->updateSubscription($newSubscription)
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new UserMembershipUpdated($newUserMembership),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function active_user_membership_then_active_subscription_with_id_check_activates_once()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new IdWasChecked($customer->id),
                new UserMembershipCreated($oldUserMembership),
                new SubscriptionCreated($this->subscription()->status('need-id-check')),
            ])
            ->updateUserMembership($newUserMembership)
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
                new MembershipActivated($customer->id),
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function active_subscription_then_active_user_membership_without_explicit_id_check_activates()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new SubscriptionCreated($this->subscription()->status('need-id-check')),
                new IdWasChecked($customer->id),
            ])
            ->updateSubscription($newSubscription)
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new UserMembershipUpdated($newUserMembership),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function active_user_membership_then_active_subscription_without_explicit_id_check_activates()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new SubscriptionCreated($this->subscription()->status('need-id-check')),
                new IdWasChecked($customer->id),
            ])
            ->updateUserMembership($newUserMembership)
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
                new MembershipActivated($customer->id),
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function active_subscription_then_active_user_membership_without_explicit_id_check_does_not_activate()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new SubscriptionCreated($this->subscription()->status('need-id-check')),
            ])
            ->updateSubscription($newSubscription)
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new UserMembershipUpdated($newUserMembership),
            ]);
    }

    /** @test */
    public function active_user_membership_then_active_subscription_without_explicit_id_check_does_not_activate()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new SubscriptionCreated($this->subscription()->status('need-id-check')),
            ])
            ->updateUserMembership($newUserMembership)
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
                new SubscriptionUpdated($newSubscription),
            ]);
    }

    /** @test */
    public function user_membership_active_then_paused_does_not_activate_membership_on_id_check()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new UserMembershipUpdated($newUserMembership),
            ])
            ->updateCustomer($customer->id_was_checked())
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_active_then_paused_then_active_again_activates_membership_on_id_check()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new UserMembershipUpdated($newUserMembership),
                new UserMembershipUpdated($oldUserMembership),
            ])
            ->updateCustomer($customer->id_was_checked())
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_active_then_paused_then_active_again_activates_membership_after_id_check()
    {
        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($oldUserMembership),
                new UserMembershipUpdated($newUserMembership),
                new IdWasChecked($customer->id),
            ])
            ->updateUserMembership($oldUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($oldUserMembership),
                new MembershipActivated($customer->id),
            ]);
    }
}
