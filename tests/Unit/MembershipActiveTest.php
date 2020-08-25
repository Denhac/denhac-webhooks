<?php

namespace Tests\Unit;

use App\Aggregates\MembershipAggregate;
use App\StorableEvents\CustomerCreated;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MembershipActiveTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    /** @test */
    public function customerCreatedByDefaultDoesNotHaveAnActiveMembership()
    {
        $customer = $this->customer();

        MembershipAggregate::fake()
            ->createCustomer($customer)
            ->assertRecorded(new CustomerCreated($customer));
    }
}
