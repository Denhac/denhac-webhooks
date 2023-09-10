<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\StorableEvents\Membership\IdWasChecked;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerUpdated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;
use App\Models\UserMembership;
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

    /**
     * @test
     * We tend not to see "expired" in the wild. Limited length memberships are rare and usually it's a subscription
     * that still has a card on file but started with a discount and has a next renewal for the length of time before
     * we want it to expire. However, on the off chance we want a user with a user membership that isn't directly tied
     * to a subscription with a card on file AND that user membership has a time limit, the status is expired so we
     * handle it here anyway.
     */
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

    /**
     * When a customer gets their ID checked, the subscription is moved from pause to active even if the subscription is
     * not on that customer specifically (e.g. for groups). This causes the team linked to that subscription to be
     * "active" (it technically doesn't have a status, but links the subscription to the user memberships) which in turn
     * tries to activate all of the user memberships on that team. In a group setting, we don't want a user membership
     * active unless they have had their ID check, so the website has code to override that behavior and make sure it
     * stays in a "paused" state.
     *
     * On very rare occasions (e.g. it shouldn't happen but I've seen it once) the user membership will emit an "active"
     * webhook before immediately going back to "paused".
     *
     * That is what the following tests verify we can handle correctly:
     * {@link user_membership_active_then_paused_does_not_activate_membership_on_id_check}
     * {@link user_membership_active_then_paused_then_active_again_activates_membership_on_id_check}
     * {@link user_membership_active_then_paused_then_active_again_activates_membership_after_id_check}
     */

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
