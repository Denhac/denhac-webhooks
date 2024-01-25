<?php

namespace Tests\Unit\Reactors;

use App\FeatureFlags;
use App\Models\Customer;
use App\Models\UserMembership;
use App\Models\VolunteerGroup;
use App\Models\VolunteerGroupChannel;
use App\Reactors\VolunteerGroupsReactor;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipDeleted;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;
use App\VolunteerGroupChannels\SlackChannel;
use App\VolunteerGroupChannels\SlackUserGroup;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\AssertsActions;
use Tests\Helpers\Wordpress\CustomerBuilder;
use Tests\Helpers\Wordpress\UserMembershipBuilder;
use Tests\TestCase;

class VolunteerGroupsReactorTest extends TestCase
{
    use AssertsActions;

    private const TEST_PLAN_A_ID = 1;
    private UserMembershipBuilder $userMembershipA;

    private const TEST_PLAN_B_ID = 2;

    private const TEST_PLAN_C_ID = 3;

    private CustomerBuilder $customerBuilder;
    private Customer $customer;

    private SlackChannel|MockInterface $slackChannelSpy;
    private SlackUserGroup|MockInterface $slackUserGroupSpy;


    protected function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(VolunteerGroupsReactor::class);

        Queue::fake();

        $this->customerBuilder = $this->customer();
        $this->customer = Customer::create([
            'id' => 1,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'username' => $this->faker->userName(),
            'email' => $this->faker->email(),
            'member' => true,
        ]);

        $this->slackChannelSpy = spy(SlackChannel::class);
        $this->slackChannelSpy->allows('removeOnMembershipLost')->andReturn(false);
        app()->instance(SlackChannel::class, $this->slackChannelSpy);

        $this->slackUserGroupSpy = spy(SlackUserGroup::class);
        $this->slackUserGroupSpy->allows('removeOnMembershipLost')->andReturn(true);
        app()->instance(SlackUserGroup::class, $this->slackUserGroupSpy);

        /** @var VolunteerGroup $volunteerGroupA */
        $volunteerGroupA = VolunteerGroup::create([
            'name' => 'Group for Plan A',
            'plan_id' => self::TEST_PLAN_A_ID,
        ]);

        VolunteerGroupChannel::create([
            'volunteer_group_id' => $volunteerGroupA->id,
            'type' => VolunteerGroupChannel::SLACK_CHANNEL_ID,
            'value' => $this->faker->uuid(),
        ]);

        $this->userMembershipA = $this->userMembership()->plan(self::TEST_PLAN_A_ID)->customer($this->customer);

        /** @var VolunteerGroup $volunteerGroupB */
        $volunteerGroupB = VolunteerGroup::create([
            'name' => 'Group for Plan B',
            'plan_id' => self::TEST_PLAN_B_ID,
        ]);

        VolunteerGroupChannel::create([
            'volunteer_group_id' => $volunteerGroupB->id,
            'type' => VolunteerGroupChannel::SLACK_CHANNEL_ID,
            'value' => $this->faker->uuid(),
        ]);

        /** @var VolunteerGroup $volunteerGroupC */
        $volunteerGroupC = VolunteerGroup::create([
            'name' => 'Group for Plan C',
            'plan_id' => self::TEST_PLAN_C_ID,
        ]);

        VolunteerGroupChannel::create([
            'volunteer_group_id' => $volunteerGroupC->id,
            'type' => VolunteerGroupChannel::SLACK_USER_GROUP_ID,
            'value' => $this->faker->uuid(),
        ]);

        // These actually get inserted in the database because they're pulled from the customer model relation
        UserMembership::create([
            'id' => 1,
            'plan_id' => self::TEST_PLAN_B_ID,
            'status' => 'active',
            'customer_id' => $this->customer->id,
        ]);
        UserMembership::create([
            'id' => 2,
            'plan_id' => self::TEST_PLAN_C_ID,
            'status' => 'active',
            'customer_id' => $this->customer->id,
        ]);
    }

    protected function verifyNoInteraction(MockInterface $spy): void
    {
        $spy->shouldNotHaveReceived('add');
        $spy->shouldNotHaveReceived('remove');
    }

    protected function verifyAddWasCalled(MockInterface $spy): void
    {
        $spy->shouldHaveReceived('add')->withArgs(function ($customer) {
            return $this->customer->id == $customer->id;
        });
    }

    protected function verifyRemoveWasCalled(MockInterface $spy): void
    {
        $spy->shouldHaveReceived('remove')->withArgs(function ($customer) {
            return $this->customer->id == $customer->id;
        });
    }

    /** @test */
    public function ff_off_slack_channel_is_not_used_on_membership_created(): void
    {
        $this->turnOff(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        event(new UserMembershipCreated($this->userMembershipA));

        $this->verifyNoInteraction($this->slackChannelSpy);
    }

    /** @test */
    public function ff_on_slack_channel_is_used_on_membership_created(): void
    {
        $this->turnOn(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        event(new UserMembershipCreated($this->userMembershipA));

        $this->verifyAddWasCalled($this->slackChannelSpy);
    }

    /**
     * @test
     * @dataProvider inactiveUserMembershipStatuses
     */
    public function ff_off_slack_channel_is_not_used_on_membership_update_to_inactive($status): void
    {
        $this->turnOff(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        $this->userMembershipA->status($status);

        event(new UserMembershipUpdated($this->userMembershipA));

        $this->verifyNoInteraction($this->slackChannelSpy);
    }

    /**
     * @test
     * @dataProvider inactiveUserMembershipStatuses
     */
    public function ff_on_slack_channel_is_used_on_membership_update_to_inactive($status): void
    {
        $this->turnOn(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        $this->userMembershipA->status($status);

        event(new UserMembershipUpdated($this->userMembershipA));

        $this->verifyRemoveWasCalled($this->slackChannelSpy);
    }

    /**
     * @test
     * @dataProvider activeUserMembershipStatuses
     */
    public function ff_off_slack_channel_is_not_used_on_membership_update_to_active($status): void
    {
        $this->turnOff(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        $this->userMembershipA->status($status);

        event(new UserMembershipUpdated($this->userMembershipA));

        $this->verifyNoInteraction($this->slackChannelSpy);
    }

    /**
     * @test
     * @dataProvider activeUserMembershipStatuses
     */
    public function ff_on_slack_channel_is_used_on_membership_update_to_active($status): void
    {
        $this->turnOn(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        $this->userMembershipA->status($status);

        event(new UserMembershipUpdated($this->userMembershipA));

        $this->verifyAddWasCalled($this->slackChannelSpy);
    }

    /** @test */
    public function ff_off_slack_channel_is_not_used_on_membership_deleted(): void
    {
        $this->turnOff(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        event(new UserMembershipDeleted($this->userMembershipA));

        $this->verifyNoInteraction($this->slackChannelSpy);
    }

    /** @test */
    public function ff_on_slack_channel_is_used_on_membership_deleted(): void
    {
        $this->turnOn(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        event(new UserMembershipDeleted($this->userMembershipA));

        $this->verifyRemoveWasCalled($this->slackChannelSpy);
    }

    /** @test */
    public function on_membership_deactivated_all_channels_that_should_be_removed_get_removed(): void
    {
        // This isn't a feature flag test, our slack channel just happens to be gated. Remove the flag when done, keep the test
        $this->turnOn(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        event(new MembershipDeactivated($this->customer->id));

        $this->verifyNoInteraction($this->slackChannelSpy);  // removeOnMembershipLost = false
        $this->verifyRemoveWasCalled($this->slackUserGroupSpy);  // removeOnMembershipLost = true
    }
}
