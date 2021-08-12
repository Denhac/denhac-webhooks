<?php

namespace Tests\Unit\Reactors;

use App\Actions\Slack\AddToChannel;
use App\Actions\Slack\AddToUserGroup;
use App\Actions\Slack\RemoveFromChannel;
use App\Actions\Slack\RemoveFromUserGroup;
use App\FeatureFlags;
use App\Jobs\DemoteMemberToPublicOnlyMemberInSlack;
use App\Jobs\InviteCustomerNeedIdCheckOnlyMemberInSlack;
use App\Jobs\MakeCustomerRegularMemberInSlack;
use App\Reactors\SlackReactor;
use App\Slack\Channels;
use App\StorableEvents\CustomerBecameBoardMember;
use App\StorableEvents\CustomerRemovedFromBoard;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\SubscriptionUpdated;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\AssertsActions;
use Tests\TestCase;
use YlsIdeas\FeatureFlags\Facades\Features;

class SlackReactorTest extends TestCase
{
    use AssertsActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(SlackReactor::class);

        Queue::fake();

        Bus::fake([
            DemoteMemberToPublicOnlyMemberInSlack::class,
            MakeCustomerRegularMemberInSlack::class,
            InviteCustomerNeedIdCheckOnlyMemberInSlack::class,
        ]);
    }

    /** @test */
    public function on_becoming_board_member_customer_is_added_to_board_slack_channel_and_group()
    {
        $customerId = 1;
        event(new CustomerBecameBoardMember($customerId));

        $this->assertAction(AddToChannel::class)
            ->with($customerId, Channels::BOARD);

        $this->assertAction(AddToUserGroup::class)
            ->with($customerId, 'theboard');
    }

    /** @test */
    public function on_removal_from_board_customer_is_removed_from_board_slack_channel_and_group()
    {
        $customer = $this->customerModel();
        event(new CustomerRemovedFromBoard($customer->id));

        $this->assertAction(RemoveFromChannel::class)
            ->with($customer->id, Channels::BOARD);

        $this->assertAction(RemoveFromUserGroup::class)
            ->with($customer->id, 'theboard');
    }

    /** @test */
    public function on_membership_deactivation_they_are_demoted_in_slack()
    {
        $customerId = 1;
        event(new MembershipDeactivated($customerId));

        Bus::assertDispatched(DemoteMemberToPublicOnlyMemberInSlack::class,
            function (DemoteMemberToPublicOnlyMemberInSlack $job) use ($customerId) {
                return $job->wooCustomerId == $customerId;
            });
    }

    /** @test */
    public function on_membership_deactivation_with_keep_members_flag_on_they_are_not_demoted()
    {
        Features::turnOn(FeatureFlags::KEEP_MEMBERS_IN_SLACK_AND_EMAIL);

        event(new MembershipDeactivated(1));

        Bus::assertNotDispatched(DemoteMemberToPublicOnlyMemberInSlack::class);
    }

    /** @test */
    public function on_membership_activation_they_are_made_a_regular_member_in_slack()
    {
        $customerId = 1;
        event(new MembershipActivated($customerId));

        Bus::assertDispatched(MakeCustomerRegularMemberInSlack::class,
            function (MakeCustomerRegularMemberInSlack $job) use ($customerId) {
                return $job->wooCustomerId == $customerId;
            });
    }

    /** @test */
    public function need_id_check_subscription_invites_as_public_only_member()
    {
        $subscription = $this->subscription()->status('need-id-check');

        event(new SubscriptionUpdated($subscription->toArray()));

        Bus::assertDispatched(InviteCustomerNeedIdCheckOnlyMemberInSlack::class,
            function (InviteCustomerNeedIdCheckOnlyMemberInSlack $job) use ($subscription) {
                return $job->wooCustomerId == $subscription->customer_id;
            });
    }

    /** @test */
    public function need_id_check_subscription_invites_regular_member_if_flag_is_set()
    {
        Features::turnOn(FeatureFlags::NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL);

        $subscription = $this->subscription()->status('need-id-check');

        event(new SubscriptionUpdated($subscription->toArray()));

        Bus::assertDispatched(MakeCustomerRegularMemberInSlack::class,
            function (MakeCustomerRegularMemberInSlack $job) use ($subscription) {
                return $job->wooCustomerId == $subscription->customer_id;
            });
    }

    /** @test */
    public function active_subscription_update_does_nothing()
    {
        $subscription = $this->subscription()->status('active');

        event(new SubscriptionUpdated($subscription->toArray()));

        Bus::assertNotDispatched(InviteCustomerNeedIdCheckOnlyMemberInSlack::class);
        Bus::assertNotDispatched(MakeCustomerRegularMemberInSlack::class);
    }
}
