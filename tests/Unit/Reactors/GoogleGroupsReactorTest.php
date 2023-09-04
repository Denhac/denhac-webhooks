<?php

namespace Tests\Unit\Reactors;

use App\Actions\Google\AddToGroup;
use App\Actions\Google\RemoveFromGroup;
use App\Customer;
use App\External\Google\GoogleApi;
use App\Reactors\GoogleGroupsReactor;
use App\StorableEvents\CustomerBecameBoardMember;
use App\StorableEvents\CustomerDeleted;
use App\StorableEvents\CustomerRemovedFromBoard;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\UserMembershipCreated;
use App\TrainableEquipment;
use App\UserMembership;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\AssertsActions;
use Tests\TestCase;

class GoogleGroupsReactorTest extends TestCase
{
    use AssertsActions;

    private MockInterface|GoogleApi $googleApi;

    private Customer $customer;

    public function setUp(): void
    {
        parent::setUp();

        $this->googleApi = $this->spy(GoogleApi::class);

        $googleGroupReactor = new GoogleGroupsReactor($this->googleApi);
        $this->withEventHandlers($googleGroupReactor);

        Queue::fake();

        /** @var Customer $customer */
        $this->customer = Customer::create([
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'username' => $this->faker->userName,
            'email' => $this->faker->email,
            'woo_id' => 1,
            'member' => true,
        ]);
    }

    /** @test */
    public function on_membership_activated_event_adds_to_members_group()
    {
        event(new MembershipActivated($this->customer->id));

        $this->assertAction(AddToGroup::class)
            ->with($this->customer->email, GoogleGroupsReactor::GROUP_MEMBERS);

        $this->assertAction(AddToGroup::class)
            ->with($this->customer->email, GoogleGroupsReactor::GROUP_ANNOUNCE);
    }

    /** @test */
    public function on_membership_deactivated_remove_from_all_lists_but_denhac()
    {
        $this->googleApi->allows('groupsForMember')
            ->withArgs([$this->customer->email])
            ->andReturn(collect([
                GoogleGroupsReactor::GROUP_DENHAC,
                GoogleGroupsReactor::GROUP_MEMBERS,
                GoogleGroupsReactor::GROUP_ANNOUNCE,
            ]));

        event(new MembershipDeactivated($this->customer->id));

        $this->assertAction(RemoveFromGroup::class)
            ->with($this->customer->email, GoogleGroupsReactor::GROUP_MEMBERS);
        $this->assertAction(RemoveFromGroup::class)
            ->never()
            ->with($this->customer->email, GoogleGroupsReactor::GROUP_DENHAC);
    }

    /** @test */
    public function on_customer_deleted_event_even_when_customer_is_already_deleted_in_db()
    {
        $this->customer->delete();

        $group = 'group@denhac.org';

        $this->googleApi->allows('groupsForMember')
            ->withArgs([$this->customer->email])
            ->andReturn(collect([$group]));

        event(new CustomerDeleted($this->customer->id));

        $this->assertAction(RemoveFromGroup::class)
            ->with($this->customer->email, $group);
    }

    /** @test */
    public function on_customer_became_board_member_adds_to_board_group()
    {
        event(new CustomerBecameBoardMember($this->customer->id));

        $this->assertAction(AddToGroup::class)
            ->with($this->customer->email, GoogleGroupsReactor::GROUP_BOARD);
    }

    /** @test */
    public function on_customer_removed_from_board_removes_from_board_group()
    {
        event(new CustomerRemovedFromBoard($this->customer->id));

        $this->assertAction(RemoveFromGroup::class)
            ->with($this->customer->email, GoogleGroupsReactor::GROUP_BOARD);
    }

    /**
     * @test
     *
     * @dataProvider userMembershipStatuses
     */
    public function on_user_membership_created_with_non_active_status_does_nothing(string $status)
    {
        if ($status == 'active') {
            $this->assertTrue(true);

            return;
        }

        $userMembership = $this->userMembership()
            ->customer($this->customer)
            ->status($status)
            ->plan(UserMembership::MEMBERSHIP_3DP_TRAINER);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToGroup::class)->never();
    }

    /** @test */
    public function being_added_to_trainable_equipment_as_user_adds_to_google_group()
    {
        $planId = 1234;
        $groupEmail = 'test@denhac.org';

        TrainableEquipment::create([
            'name' => 'Test',
            'user_plan_id' => $planId,
            'user_email' => $groupEmail,
            'trainer_plan_id' => 5678,
        ]);

        $userMembership = $this->userMembership()
            ->customer($this->customer)
            ->status('active')
            ->plan($planId);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToGroup::class)
            ->with($this->customer->email, $groupEmail);
    }

    /** @test */
    public function being_added_to_trainable_equipment_as_trainer_adds_to_google_group()
    {
        $planId = 1234;
        $groupEmail = 'test@denhac.org';

        TrainableEquipment::create([
            'name' => 'Test',
            'user_plan_id' => 5678,
            'trainer_plan_id' => $planId,
            'trainer_email' => $groupEmail,
        ]);

        $userMembership = $this->userMembership()
            ->customer($this->customer)
            ->status('active')
            ->plan($planId);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToGroup::class)
            ->with($this->customer->email, $groupEmail);
    }

    /** @test */
    public function being_added_to_trainable_equipment_as_user_does_not_add_to_google_group_if_it_is_null()
    {
        $planId = 1234;

        TrainableEquipment::create([
            'name' => 'Test',
            'user_plan_id' => $planId,
            'trainer_plan_id' => 5678,
        ]);

        $userMembership = $this->userMembership()
            ->customer($this->customer)
            ->status('active')
            ->plan($planId);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToGroup::class)->never();
    }

    /** @test */
    public function being_added_to_trainable_equipment_as_trainer_does_not_add_to_google_group_if_it_is_null()
    {
        $planId = 1234;

        TrainableEquipment::create([
            'name' => 'Test',
            'user_plan_id' => 5678,
            'trainer_plan_id' => $planId,
        ]);

        $userMembership = $this->userMembership()
            ->customer($this->customer)
            ->status('active')
            ->plan($planId);

        event(new UserMembershipCreated($userMembership));

        $this->assertAction(AddToGroup::class)->never();
    }
}
