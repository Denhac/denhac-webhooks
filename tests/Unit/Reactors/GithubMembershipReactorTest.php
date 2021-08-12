<?php

namespace Tests\Unit\Reactors;

use App\Actions\GitHub\AddToGitHubTeam;
use App\Customer;
use App\Jobs\RemoveMemberFromGithub;
use App\Reactors\GithubMembershipReactor;
use App\StorableEvents\GithubUsernameUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\AssertsActions;
use Tests\TestCase;

class GithubMembershipReactorTest extends TestCase
{
    use AssertsActions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(GithubMembershipReactor::class);

        Queue::fake();

        Bus::fake([
            RemoveMemberFromGithub::class,
        ]);
    }

    /** @test */
    public function test_github_username_updated_from_null_when_member()
    {
        $username = 'test';
        event(new GithubUsernameUpdated(null, $username, true));

        $this->assertAction(AddToGitHubTeam::class)
            ->with($username, 'members');

        Bus::assertNotDispatched(RemoveMemberFromGithub::class);
    }

    /** @test */
    public function test_github_username_updated_from_null_when_not_member()
    {
        $username = 'test';
        event(new GithubUsernameUpdated(null, $username, false));

        $this->assertAction(AddToGitHubTeam::class)->never();
        Bus::assertNotDispatched(RemoveMemberFromGithub::class);
    }

    /** @test */
    public function test_github_username_updated_from_name_to_null_when_member()
    {
        $username = 'test';
        event(new GithubUsernameUpdated($username, null, true));

        $this->assertAction(AddToGitHubTeam::class)->never();
        Bus::assertDispatched(RemoveMemberFromGithub::class,
            function (RemoveMemberFromGithub $job) use ($username) {
                return $job->username == $username &&
                    $job->team == 'members';
            });
    }

    /** @test */
    public function test_github_username_updated_from_name_to_null_when_not_member()
    {
        $username = 'test';
        event(new GithubUsernameUpdated($username, null, false));

        $this->assertAction(AddToGitHubTeam::class)->never();
        Bus::assertDispatched(RemoveMemberFromGithub::class,
            function (RemoveMemberFromGithub $job) use ($username) {
                return $job->username == $username &&
                    $job->team == 'members';
            });
    }

    /** @test */
    public function on_membership_activated_customer_is_added_to_github()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'username' => 'something',
            'email' => 'test@email.com',
            'woo_id' => 1,
            // Their membership is being activated, this field is probably false in db
            'member' => false,
            'github_username' => 'test',
        ]);

        event(new MembershipActivated($customer->woo_id));

        $this->assertAction(AddToGitHubTeam::class)
            ->with($customer->github_username, 'members');
        Bus::assertNotDispatched(RemoveMemberFromGithub::class);
    }

    /** @test */
    public function on_membership_deactivated_customer_is_removed_from_github()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'username' => 'something',
            'email' => 'test@email.com',
            'woo_id' => 1,
            // Their membership is being deactivated, this field is probably true in db
            'member' => true,
            'github_username' => 'test',
        ]);

        event(new MembershipDeactivated($customer->woo_id));

        $this->assertAction(AddToGitHubTeam::class)->never();
        Bus::assertDispatched(RemoveMemberFromGithub::class,
            function (RemoveMemberFromGithub $job) use ($customer) {
                return $job->username == $customer->github_username &&
                    $job->team == 'members';
            });
    }

    /** @test */
    public function on_membership_activated_with_null_github_username_customer_is_not_added_to_github()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'username' => 'something',
            'email' => 'test@email.com',
            'woo_id' => 1,
            // Their membership is being activated, this field is probably false in db
            'member' => false,
            'github_username' => null,
        ]);

        event(new MembershipActivated($customer->woo_id));

        $this->assertAction(AddToGitHubTeam::class)->never();
        Bus::assertNotDispatched(RemoveMemberFromGithub::class);
    }

    /** @test */
    public function on_membership_deactivated_with_null_github_username_customer_is_not_removed_from_github()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'username' => 'something',
            'email' => 'test@email.com',
            'woo_id' => 1,
            // Their membership is being deactivated, this field is probably true in db
            'member' => true,
            'github_username' => null,
        ]);

        event(new MembershipDeactivated($customer->woo_id));

        $this->assertAction(AddToGitHubTeam::class)->never();
        Bus::assertNotDispatched(RemoveMemberFromGithub::class);
    }
}
