<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;


use App\Aggregates\MembershipAggregate;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\GithubUsernameUpdated;
use App\StorableEvents\MembershipActivated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class GithubUsernameTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function active_member_who_updates_github_username_gets_updated_event()
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
                new GithubUsernameUpdated(null, $username, true),
            ]);
    }

    /** @test */
    public function inactive_member_who_updates_github_username_gets_updated_event()
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
                new GithubUsernameUpdated(null, $username, false),
            ]);
    }

    /** @test */
    public function member_who_changes_github_username_gets_updated_event()
    {
        $customer = $this->customer();
        $oldUsername = 'test';
        $newUsername = 'testing';

        $aggregate = MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer),
                new GithubUsernameUpdated(null, $oldUsername, false),
            ]);

        $customer->github_username($newUsername);

        $aggregate
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
                new GithubUsernameUpdated($oldUsername, $newUsername, false),
            ]);
    }
}
