<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\FeatureFlags;
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
use YlsIdeas\FeatureFlags\Facades\Features;

class ActiveMembershipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function going_from_need_id_check_to_active_subscription_activates_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function ff_on_going_from_need_id_check_to_active_subscription_does_not_activate_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
    public function going_from_id_was_check_to_active_subscription_activates_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
                new MembershipActivated($customer->id),
            ]);
    }
    /** @test */
    public function ff_on_going_from_id_was_check_to_active_subscription_does_not_activate_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
    public function going_from_null_to_active_subscription_activates_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $newSubscription = $this->subscription()->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
            ])
            ->updateSubscription($newSubscription)
            ->assertRecorded([
                new SubscriptionUpdated($newSubscription),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function ff_on_going_from_null_to_active_subscription_does_not_activate_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
    public function going_from_active_to_cancelled_subscription_deactivates_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
    public function ff_on_going_from_active_to_cancelled_subscription_does_not_deactivate_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
            ]);
    }

    /** @test */
    public function going_from_active_to_suspended_payment_subscription_deactivates_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
    public function ff_on_going_from_active_to_suspended_payment_subscription_deactivates_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
            ]);
    }

    /** @test */
    public function going_from_active_to_suspended_manual_subscription_deactivates_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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

    /** @test */
    public function ff_on_going_from_active_to_suspended_manual_subscription_does_not_deactivate_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
            ]);
    }

    /** @test */
    public function canceling_one_subscription_with_another_still_active_does_not_deactivate_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
    public function ff_on_canceling_one_subscription_with_another_still_active_does_not_deactivate_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
    public function user_membership_from_need_id_check_to_active_activates_membership_with_id_check()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('need-id-check');
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
    public function ff_off_user_membership_from_need_id_check_to_active_does_not_activate_membership_with_id_check()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('need-id-check');
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
            ]);
    }

    /** @test */
    public function user_membership_from_id_was_checked_to_active_activates_membership_with_id_check()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('id-was-checked');
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
    public function ff_off_user_membership_from_id_was_checked_to_active_does_not_activate_membership_with_id_check()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('id-was-checked');
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
            ]);
    }

    /** @test */
    public function user_membership_with_different_plan_to_active_does_not_activate_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_3DP_USER)
            ->status('need-id-check');
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
    public function ff_off_user_membership_with_different_plan_to_active_does_not_activate_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_3DP_USER)
            ->status('need-id-check');
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
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_3DP_USER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($newUserMembership),
            ])
            ->updateCustomer($customer->meta_data('id_was_checked', true))
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
            ]);
    }

    /** @test */
    public function ff_off_user_membership_with_different_plan_to_active_does_not_activate_membership_on_id_check()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_3DP_USER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($newUserMembership),
            ])
            ->updateCustomer($customer->meta_data('id_was_checked', true))
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_from_need_id_check_to_active_does_not_activate_membership_without_id_check()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('need-id-check');
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
    public function ff_off_user_membership_from_need_id_check_to_active_does_not_activate_membership_without_id_check()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('need-id-check');
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
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $userMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($userMembership),
            ])
            ->updateCustomer($customer->meta_data('id_was_checked', true))
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function ff_off_user_membership_when_active_emits_does_not_emit_membership_activated_if_id_is_checked()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $userMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new UserMembershipCreated($userMembership),
            ])
            ->updateCustomer($customer->meta_data('id_was_checked', true))
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
            ]);
    }

    /** @test */
    public function user_membership_from_active_to_cancelled_deactivates_membership()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
    public function ff_off_user_membership_from_active_to_cancelled_does_not_deactivate_membership()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

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
            ]);
    }

    /** @test */
    public function user_membership_with_two_updates_works()
    {
        Features::turnOn(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('need-id-check');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new IdWasChecked($customer->id),
                new UserMembershipCreated($oldUserMembership),
                new UserMembershipUpdated($oldUserMembership->status('id-was-checked'))
            ])
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
                new MembershipActivated($customer->id),
            ]);
    }

    /** @test */
    public function ff_off_user_membership_with_two_updates_works()
    {
        Features::turnOff(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE);

        $customer = $this->customer();

        $oldUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('need-id-check');
        $newUserMembership = $this->userMembership()->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('active');

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new IdWasChecked($customer->id),
                new UserMembershipCreated($oldUserMembership),
                new UserMembershipUpdated($oldUserMembership->status('id-was-checked'))
            ])
            ->updateUserMembership($newUserMembership)
            ->assertRecorded([
                new UserMembershipUpdated($newUserMembership),
            ]);
    }
}
