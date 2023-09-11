<?php

namespace Tests\Unit\Reactors;

use App\Actions\GitHub\AddToGitHubTeam;
use App\Actions\GitHub\RemoveFromGitHubTeam;
use App\Models\Customer;
use App\External\GitHub\GitHubApi;
use App\External\GitHub\TeamApi;
use App\Reactors\GithubMembershipReactor;
use App\StorableEvents\GitHub\GitHubUsernameUpdated;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
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

        $memberTeamApi = $this->spy(TeamApi::class);
        $gitHubApi = $this->spy(GitHubApi::class);
        $gitHubApi->allows('team')
            ->withArgs(['members'])
            ->andReturn($memberTeamApi);
        $memberTeamApi->allows('list')
            ->andReturn(collect([['login' => 'test']]));

        Queue::fake();
    }

    /** @test */
    public function test_github_username_updated_from_null_when_member()
    {
        $username = 'test';
        event(new GitHubUsernameUpdated(null, $username, true));

        $this->assertAction(AddToGitHubTeam::class)
            ->with($username, 'members');

        $this->assertAction(RemoveFromGitHubTeam::class)->never();
    }

    /** @test */
    public function test_github_username_updated_from_null_when_not_member()
    {
        $username = 'test';
        event(new GitHubUsernameUpdated(null, $username, false));

        $this->assertAction(AddToGitHubTeam::class)->never();
        $this->assertAction(RemoveFromGitHubTeam::class)->never();
    }

    /** @test */
    public function test_github_username_updated_from_name_to_null_when_member()
    {
        $username = 'test';
        event(new GitHubUsernameUpdated($username, null, true));

        $this->assertAction(AddToGitHubTeam::class)->never();
        $this->assertAction(RemoveFromGitHubTeam::class)
            ->with($username, 'members');
    }

    /** @test */
    public function test_github_username_updated_from_name_to_null_when_not_member()
    {
        $username = 'test';
        event(new GitHubUsernameUpdated($username, null, false));

        $this->assertAction(AddToGitHubTeam::class)->never();
        $this->assertAction(RemoveFromGitHubTeam::class)
            ->with($username, 'members');
    }

    /** @test */
    public function test_github_username_updated_from_bad_username_does_not_emit_remove_action()
    {
        $badUsername = 'https://github.com/example';
        event(new GitHubUsernameUpdated($badUsername, null, false));

        $this->assertAction(AddToGitHubTeam::class)->never();
        $this->assertAction(RemoveFromGitHubTeam::class)->never();
    }

    /** @test */
    public function on_membership_activated_customer_is_added_to_github()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'id' => 1,
            'username' => 'something',
            'email' => 'test@email.com',
            // Their membership is being activated, this field is probably false in db
            'member' => false,
            'github_username' => 'test',
        ]);

        event(new MembershipActivated($customer->id));

        $this->assertAction(AddToGitHubTeam::class)
            ->with($customer->github_username, 'members');
        $this->assertAction(RemoveFromGitHubTeam::class)->never();
    }

    /** @test */
    public function on_membership_deactivated_customer_is_removed_from_github()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'id' => 1,
            'username' => 'something',
            'email' => 'test@email.com',
            // Their membership is being deactivated, this field is probably true in db
            'member' => true,
            'github_username' => 'test',
        ]);

        event(new MembershipDeactivated($customer->id));

        $this->assertAction(AddToGitHubTeam::class)->never();
        $this->assertAction(RemoveFromGitHubTeam::class)
            ->with($customer->github_username, 'members');
    }

    /** @test */
    public function on_membership_activated_with_null_github_username_customer_is_not_added_to_github()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'id' => 1,
            'username' => 'something',
            'email' => 'test@email.com',
            // Their membership is being activated, this field is probably false in db
            'member' => false,
            'github_username' => null,
        ]);

        event(new MembershipActivated($customer->id));

        $this->assertAction(AddToGitHubTeam::class)->never();
        $this->assertAction(RemoveFromGitHubTeam::class)->never();
    }

    /** @test */
    public function on_membership_deactivated_with_null_github_username_customer_is_not_removed_from_github()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'id' => 1,
            'username' => 'something',
            'email' => 'test@email.com',
            // Their membership is being deactivated, this field is probably true in db
            'member' => true,
            'github_username' => null,
        ]);

        event(new MembershipDeactivated($customer->id));

        $this->assertAction(AddToGitHubTeam::class)->never();
        $this->assertAction(RemoveFromGitHubTeam::class)->never();
    }
}
