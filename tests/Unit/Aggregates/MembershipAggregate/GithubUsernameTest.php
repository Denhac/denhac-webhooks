<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\StorableEvents\GitHub\GitHubUsernameUpdated;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class GithubUsernameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function active_member_who_updates_github_username_gets_updated_event(): void
    {
        $customer = $this->customer();

        $aggregate = MembershipAggregate::fakeCustomer($customer)
            ->given([
                new MembershipActivated($customer),
                new CustomerCreated($customer),
            ]);

        $username = 'test';
        $customer->github_username($username);

        $aggregate
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
                new GitHubUsernameUpdated(null, $username, true),
            ]);
    }

    /** @test */
    public function inactive_member_who_updates_github_username_gets_updated_event(): void
    {
        $customer = $this->customer();

        $aggregate = MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
            ]);

        $username = 'test';
        $customer->github_username($username);

        $aggregate
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
                new GitHubUsernameUpdated(null, $username, false),
            ]);
    }

    /** @test */
    public function member_who_changes_github_username_gets_updated_event(): void
    {
        $customer = $this->customer();
        $oldUsername = 'test';
        $newUsername = 'testing';

        $aggregate = MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new GitHubUsernameUpdated(null, $oldUsername, false),
            ]);

        $customer->github_username($newUsername);

        $aggregate
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
                new GitHubUsernameUpdated($oldUsername, $newUsername, false),
            ]);
    }
}
