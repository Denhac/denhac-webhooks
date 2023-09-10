<?php

namespace Tests\Unit\Aggregates\MembershipAggregate;

use App\Aggregates\MembershipAggregate;
use App\StorableEvents\Membership\IdWasChecked;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerImported;
use App\StorableEvents\WooCommerce\CustomerUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\EventSourcing\Facades\Projectionist;
use Tests\TestCase;

class IdCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Projectionist::withoutEventHandlers();
    }

    /** @test */
    public function id_was_checked_is_emitted_when_user_meta_key_is_present_on_import()
    {
        $customer = $this->customer()
            ->meta_data('id_was_checked', true);

        MembershipAggregate::fakeCustomer($customer)
            ->importCustomer($customer)
            ->assertRecorded([
                new CustomerImported($customer),
                new IdWasChecked($customer->id),
            ]);
    }

    /** @test */
    public function id_was_checked_is_emitted_when_user_meta_key_is_present_on_create()
    {
        $customer = $this->customer()
            ->meta_data('id_was_checked', true);

        MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->assertRecorded([
                new CustomerCreated($customer),
                new IdWasChecked($customer->id),
            ]);
    }

    /** @test */
    public function id_was_checked_is_emitted_when_user_meta_key_is_present_on_update()
    {
        $customer = $this->customer();

        MembershipAggregate::fakeCustomer($customer)
            ->given([
                new CustomerCreated($customer->meta_data('id_was_checked', true)),
            ])
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerUpdated($customer),
                new IdWasChecked($customer->id),
            ]);
    }

    /** @test */
    public function id_was_checked_is_only_emitted_once()
    {
        $customer = $this->customer()
            ->meta_data('id_was_checked', true);

        MembershipAggregate::fakeCustomer($customer)
            ->createCustomer($customer)
            ->updateCustomer($customer)
            ->assertRecorded([
                new CustomerCreated($customer),
                new IdWasChecked($customer->id),
                new CustomerUpdated($customer),
            ]);
    }
}
