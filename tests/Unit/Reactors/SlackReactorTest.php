<?php

namespace Tests\Unit\Reactors;

use App\Actions\Slack\AddToChannel;
use App\Jobs\DemoteMemberToPublicOnlyMemberInSlack;
use App\Jobs\InviteCustomerNeedIdCheckOnlyMemberInSlack;
use App\Jobs\MakeCustomerRegularMemberInSlack;
use App\Models\TrainableEquipment;
use App\Reactors\SlackReactor;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\AssertsActions;
use Tests\TestCase;

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
    public function on_membership_deactivation_they_are_demoted_in_slack(): void
    {
        $customerId = 1;
        event(new MembershipDeactivated($customerId));

        Bus::assertDispatched(DemoteMemberToPublicOnlyMemberInSlack::class,
            function (DemoteMemberToPublicOnlyMemberInSlack $job) use ($customerId) {
                return $job->wooCustomerId == $customerId;
            });
    }

    /** @test */
    public function on_membership_activation_they_are_made_a_regular_member_in_slack(): void
    {
        $customerId = 1;
        event(new MembershipActivated($customerId));

        Bus::assertDispatched(MakeCustomerRegularMemberInSlack::class,
            function (MakeCustomerRegularMemberInSlack $job) use ($customerId) {
                return $job->wooCustomerId == $customerId;
            });
    }

    /** @test */
    public function being_added_to_trainable_equipment_as_user_adds_to_slack_channel(): void
    {
        $planId = 1234;
        $slackId = 'C1345348';
        $customerId = 27;

        TrainableEquipment::create([
            'name' => 'Test',
            'user_plan_id' => $planId,
            'user_slack_id' => $slackId,
            'trainer_plan_id' => 5678,
        ]);

        $userMembership = $this->userMembership()
            ->customer($customerId)
            ->status('active')
            ->plan($planId);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToChannel::class)
            ->with($customerId, $slackId);
    }

    /** @test */
    public function being_added_to_trainable_equipment_as_trainer_adds_to_slack_channel(): void
    {
        $planId = 1234;
        $slackId = 'C1345348';
        $customerId = 27;

        TrainableEquipment::create([
            'name' => 'Test',
            'user_plan_id' => 5678,
            'trainer_plan_id' => $planId,
            'trainer_slack_id' => $slackId,
        ]);

        $userMembership = $this->userMembership()
            ->customer($customerId)
            ->status('active')
            ->plan($planId);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToChannel::class)
            ->with($customerId, $slackId);
    }

    /** @test */
    public function being_added_to_trainable_equipment_as_user_does_not_add_to_slack_channel_with_null_channel(): void
    {
        $planId = 1234;
        $customerId = 27;

        TrainableEquipment::create([
            'name' => 'Test',
            'user_plan_id' => $planId,
            'trainer_plan_id' => 5678,
        ]);

        $userMembership = $this->userMembership()
            ->customer($customerId)
            ->status('active')
            ->plan($planId);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToChannel::class)->never();
    }

    /** @test */
    public function being_added_to_trainable_equipment_as_trainer_does_not_add_to_slack_channel_with_null_channel(): void
    {
        $planId = 1234;
        $customerId = 27;

        TrainableEquipment::create([
            'name' => 'Test',
            'user_plan_id' => 5678,
            'trainer_plan_id' => $planId,
        ]);

        $userMembership = $this->userMembership()
            ->customer($customerId)
            ->status('active')
            ->plan($planId);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToChannel::class)->never();
    }
}
