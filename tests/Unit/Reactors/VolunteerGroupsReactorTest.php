<?php

namespace Tests\Unit\Reactors;

use App\FeatureFlags;
use App\Models\Customer;
use App\Models\VolunteerGroup;
use App\Models\VolunteerGroupChannel;
use App\Reactors\VolunteerGroupsReactor;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
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
    private UserMembershipBuilder $userMembershipB;

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
        app()->instance(SlackChannel::class, $this->slackChannelSpy);

        $this->slackUserGroupSpy = spy(SlackUserGroup::class);
        app()->instance(SlackUserGroup::class, $this->slackUserGroupSpy);

        /** @var VolunteerGroup $volunteerGroupA */
        $volunteerGroupA = VolunteerGroup::create([
            'name' => 'Group for Plan A',
            'plan_id' => self::TEST_PLAN_A_ID,
        ]);

        VolunteerGroupChannel::create([
            'volunteer_group_id' => $volunteerGroupA->id,
            'type' => VolunteerGroupChannel::SLACK_CHANNEL_ID,
            'value' => $this->faker->uuid(), // This isn't a uuid in real life but it doesn't matter here. What matters is that this is the only channel that's a slack channel type.
        ]);

        $this->userMembershipA = $this->userMembership()->plan(self::TEST_PLAN_A_ID)->customer($this->customer);

        /** @var VolunteerGroup $volunteerGroupB1 */
        $volunteerGroupB1 = VolunteerGroup::create([
            'name' => 'Group 1 for Plan B',
            'plan_id' => self::TEST_PLAN_B_ID,
        ]);

        VolunteerGroupChannel::create([
            'volunteer_group_id' => $volunteerGroupB1->id,
            'type' => VolunteerGroupChannel::SLACK_CHANNEL_ID,
            'value' => $this->faker->uuid(),
        ]);

        /** @var VolunteerGroup $volunteerGroupB2 */
        $volunteerGroupB2 = VolunteerGroup::create([
            'name' => 'Group 2 for Plan B',
            'plan_id' => self::TEST_PLAN_B_ID,
        ]);

        VolunteerGroupChannel::create([
            'volunteer_group_id' => $volunteerGroupB2->id,
            'type' => VolunteerGroupChannel::SLACK_USER_GROUP_ID,
            'value' => $this->faker->uuid(),
        ]);

        $this->userMembershipB = $this->userMembership()->plan(self::TEST_PLAN_B_ID)->customer($this->customer);
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

    /** @test */
    public function multiple_volunteer_groups_with_the_same_plan_id_all_get_added(): void
    {
        // This isn't a feature flag test, our slack channel just happens to be gated. Remove the flag when done, keep the test
        $this->turnOn(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        event(new UserMembershipCreated($this->userMembershipB));

        $this->verifyAddWasCalled($this->slackChannelSpy);
        $this->verifyAddWasCalled($this->slackUserGroupSpy);
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
     * @dataProvider inactiveUserMembershipStatuses
     */
    public function multiple_volunteer_groups_with_the_same_plan_id_all_get_removed_on_update_to_inactive($status): void
    {
        // This isn't a feature flag test, our slack channel just happens to be gated. Remove the flag when done, keep the test
        $this->turnOn(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        $this->userMembershipB->status($status);

        event(new UserMembershipUpdated($this->userMembershipB));

        $this->verifyRemoveWasCalled($this->slackChannelSpy);
        $this->verifyRemoveWasCalled($this->slackUserGroupSpy);
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

    /**
     * @test
     * @dataProvider activeUserMembershipStatuses
     */
    public function multiple_volunteer_groups_with_the_same_plan_id_all_get_added_on_update_to_active($status): void
    {
        // This isn't a feature flag test, our slack channel just happens to be gated. Remove the flag when done, keep the test
        $this->turnOn(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS);

        $this->userMembershipB->status($status);

        event(new UserMembershipUpdated($this->userMembershipB));

        $this->verifyAddWasCalled($this->slackChannelSpy);
        $this->verifyAddWasCalled($this->slackUserGroupSpy);
    }

    /*
     * Tests:
     * - on 6410 membership de-activated, remove them from all channels
     * - on 6410 membership activated, add them to all channels
     * - on user membership deleted, remove them from those groups
     *  - assuming we can find it in our database (second test)
     */
}
