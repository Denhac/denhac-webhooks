<?php

namespace Tests\Unit\Reactors;


use App\Customer;
use App\Jobs\AddMemberToGithub;
use App\Jobs\RemoveMemberFromGithub;
use App\Reactors\GithubMembershipReactor;
use App\StorableEvents\GithubUsernameUpdated;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class GithubMembershipReactorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandler(GithubMembershipReactor::class);

        Bus::fake([
            AddMemberToGithub::class,
            RemoveMemberFromGithub::class
        ]);
    }

    /** @test */
    public function test_github_username_updated_from_null_when_member()
    {
        $username = 'test';
        event(new GithubUsernameUpdated(null, $username, true));

        Bus::assertDispatched(AddMemberToGithub::class,
            function (AddMemberToGithub $job) use ($username) {
                return $job->username == $username &&
                    $job->team == 'members';
            });
        Bus::assertNotDispatched(RemoveMemberFromGithub::class);
    }

    /** @test */
    public function test_github_username_updated_from_null_when_not_member()
    {
        $username = 'test';
        event(new GithubUsernameUpdated(null, $username, false));

        Bus::assertNotDispatched(AddMemberToGithub::class);
        Bus::assertNotDispatched(RemoveMemberFromGithub::class);
    }

    /** @test */
    public function test_github_username_updated_from_name_to_null_when_member()
    {
        $username = 'test';
        event(new GithubUsernameUpdated($username, null, true));

        Bus::assertNotDispatched(AddMemberToGithub::class);
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

        Bus::assertNotDispatched(AddMemberToGithub::class);
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
            # Their membership is being activated, this field is probably false in db
            'member' => false,
            'github_username' => 'test',
        ]);

        event(new MembershipActivated($customer->woo_id));

        Bus::assertDispatched(AddMemberToGithub::class,
            function (AddMemberToGithub $job) use ($customer) {
                return $job->username == $customer->github_username &&
                    $job->team == 'members';
            });
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
            # Their membership is being deactivated, this field is probably true in db
            'member' => true,
            'github_username' => 'test',
        ]);

        event(new MembershipDeactivated($customer->woo_id));

        Bus::assertNotDispatched(AddMemberToGithub::class);
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
            # Their membership is being activated, this field is probably false in db
            'member' => false,
            'github_username' => null,
        ]);

        event(new MembershipActivated($customer->woo_id));

        Bus::assertNotDispatched(AddMemberToGithub::class);
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
            # Their membership is being deactivated, this field is probably true in db
            'member' => true,
            'github_username' => null,
        ]);

        event(new MembershipDeactivated($customer->woo_id));

        Bus::assertNotDispatched(AddMemberToGithub::class);
        Bus::assertNotDispatched(RemoveMemberFromGithub::class);
    }
}
