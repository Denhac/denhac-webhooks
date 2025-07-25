<?php

namespace Tests\Feature\Http\Slack;

use App\External\Slack\Channels;
use App\External\Slack\MembershipType;
use App\Models\Customer;
use App\Models\UserMembership;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitesNeededControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Passport::actingAs($this->apiUser, ['slack:invite']);
    }

    protected function getInvites(): TestResponse
    {
        return $this->get('/api/slack/invites')
            ->assertStatus(200);
    }

    #[Test] public function by_default_the_list_is_empty(): void
    {
        $this->getInvites()
            ->assertExactJson([]);
    }

    #[Test] public function a_customer_with_no_user_membership(): void
    {
        Customer::factory()->create();

        $this->getInvites()
            ->assertExactJson([]);
    }

    #[Test] public function a_customer_without_an_id_check(): void
    {
        $userMembership = UserMembership::factory()->paused();
        /** @var Customer $customer */
        $customer = Customer::factory()->has($userMembership, 'memberships')->create();

        // Not being a member or having their id checked means they only need to be in the need-id-check channel
        $this->assertFalse($customer->id_checked);
        $this->assertFalse($customer->member);

        $this->getInvites()
            ->assertExactJson([
                [
                    'email' => $customer->email,
                    'type' => MembershipType::SINGLE_CHANNEL_GUEST,
                    'channels' => [
                        Channels::NEED_ID_CHECK,
                    ]
                ]
            ]);
    }

    #[Test] public function a_customer_with_an_id_check_but_a_lapsed_membership(): void
    {
        $userMembership = UserMembership::factory()->paused();
        /** @var Customer $customer */
        $customer = Customer::factory()
            ->has($userMembership, 'memberships')
            ->idChecked()
            ->create();

        // Not being a member but having their id checked means they belong in the public channel.
        $this->assertTrue($customer->id_checked);
        $this->assertFalse($customer->member);

        $this->getInvites()
            ->assertExactJson([
                [
                    'email' => $customer->email,
                    'type' => MembershipType::SINGLE_CHANNEL_GUEST,
                    'channels' => [
                        Channels::PUBLIC,
                    ]
                ]
            ]);
    }

    #[Test] public function an_active_member(): void
    {
        $userMembership = UserMembership::factory();
        /** @var Customer $customer */
        $customer = Customer::factory()
            ->has($userMembership, 'memberships')
            ->idChecked()
            ->member()
            ->create();

        // Not being a member but having their id checked means they belong in the public channel.
        $this->assertTrue($customer->id_checked);
        $this->assertTrue($customer->member);

        $this->getInvites()
            ->assertExactJson([
                [
                    'email' => $customer->email,
                    'type' => MembershipType::FULL_USER,
                    'channels' => [
                        Channels::GENERAL,
                        Channels::PUBLIC,
                        Channels::RANDOM,
                    ]
                ]
            ]);
    }

    #[Test] public function a_deleted_customer(): void
    {
        $userMembership = UserMembership::factory()->paused();
        Customer::factory()
            ->has($userMembership, 'memberships')
            ->trashed()
            ->create();

        $this->getInvites()
            ->assertExactJson([]);
    }

    #[Test] public function a_cancelled_user_membership(): void
    {
        $userMembership = UserMembership::factory()->cancelled();
        Customer::factory()
            ->has($userMembership, 'memberships')
            ->create();

        $this->getInvites()
            ->assertExactJson([]);
    }

    #[Test] public function a_customer_with_an_existing_slack_id(): void
    {
        $userMembership = UserMembership::factory();
        Customer::factory()
            ->has($userMembership, 'memberships')
            ->withSlackId()
            ->create();

        $this->getInvites()
            ->assertExactJson([]);
    }
}
