<?php

namespace Tests\Unit\Projectors;

use App\Models\Customer;
use App\Projectors\CustomerProjector;
use App\StorableEvents\Membership\IdWasChecked;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\CustomerImported;
use App\StorableEvents\WooCommerce\CustomerUpdated;
use App\StorableEvents\WooCommerce\SubscriptionImported;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CustomerProjectorTest extends TestCase
{
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(CustomerProjector::class);
    }

    /** @test */
    public function importing_a_customer_creates_customer_in_db()
    {
        $builder = $this->customer();

        $this->assertNull(Customer::find($builder->id));

        event(new CustomerImported($builder->toArray()));

        /** @var Customer $customer */
        $customer = Customer::find($builder->id);

        $this->assertNotNull($customer);
        $this->assertEquals($builder->username, $customer->username);
        $this->assertEquals($builder->email, $customer->email);
        $this->assertEquals($builder->first_name, $customer->first_name);
        $this->assertEquals($builder->last_name, $customer->last_name);
        $this->assertEquals($builder->github_username, $customer->github_username);
        $this->assertEquals($builder->slack_id, $customer->slack_id);
        $this->assertEquals($builder->birthday, $customer->birthday);
        $this->assertFalse($customer->member);
    }

    /** @test */
    public function creating_a_customer_creates_customer_in_db()
    {
        $builder = $this->customer();

        $this->assertNull(Customer::find($builder->id));

        event(new CustomerCreated($builder->toArray()));

        /** @var Customer $customer */
        $customer = Customer::find($builder->id);

        $this->assertNotNull($customer);
        $this->assertEquals($builder->username, $customer->username);
        $this->assertEquals($builder->email, $customer->email);
        $this->assertEquals($builder->first_name, $customer->first_name);
        $this->assertEquals($builder->last_name, $customer->last_name);
        $this->assertEquals($builder->github_username, $customer->github_username);
        $this->assertEquals($builder->slack_id, $customer->slack_id);
        $this->assertEquals($builder->birthday, $customer->birthday);
        $this->assertFalse($customer->member);
    }

    /** @test */
    public function updating_a_non_existent_customer_creates_customer_in_db()
    {
        $builder = $this->customer();

        $this->assertNull(Customer::find($builder->id));

        event(new CustomerUpdated($builder->toArray()));

        /** @var Customer $customer */
        $customer = Customer::find($builder->id);

        $this->assertNotNull($customer);
        $this->assertEquals($builder->username, $customer->username);
        $this->assertEquals($builder->email, $customer->email);
        $this->assertEquals($builder->first_name, $customer->first_name);
        $this->assertEquals($builder->last_name, $customer->last_name);
        $this->assertEquals($builder->github_username, $customer->github_username);
        $this->assertEquals($builder->slack_id, $customer->slack_id);
        $this->assertEquals($builder->birthday, $customer->birthday);
        $this->assertFalse($customer->member);
    }

    /** @test */
    public function updating_a_customer_updates_customer_in_db()
    {
        $builder = $this->customer();

        $this->assertNull(Customer::find($builder->id));

        event(new CustomerCreated($builder->toArray()));

        $builder->username = $this->faker->userName;
        $builder->email = $this->faker->email;
        $builder->first_name = $this->faker->firstName;
        $builder->last_name = $this->faker->lastName;
        $builder->github_username = $this->faker->userName;
        $builder->slack_id = 'U'.$this->faker->randomNumber();
        $builder->birthday = $this->faker->date;

        event(new CustomerUpdated($builder->toArray()));

        /** @var Customer $customer */
        $customer = Customer::find($builder->id);

        $this->assertNotNull($customer);
        $this->assertEquals($builder->username, $customer->username);
        $this->assertEquals($builder->email, $customer->email);
        $this->assertEquals($builder->first_name, $customer->first_name);
        $this->assertEquals($builder->last_name, $customer->last_name);
        $this->assertEquals($builder->github_username, $customer->github_username);
        $this->assertEquals($builder->slack_id, $customer->slack_id);
        $this->assertEquals($builder->birthday, $customer->birthday->toDateString());
        $this->assertFalse($customer->member);
    }

    /** @test */
    public function deleting_a_customer_deletes_in_db()
    {
        $builder = $this->customer();

        $this->assertNull(Customer::find($builder->id));

        event(new CustomerCreated($builder->toArray()));

        $this->assertNotNull(Customer::find($builder->id));

        event(new CustomerDeleted($builder->toArray()));

        $this->assertNull(Customer::find($builder->id));
    }

    /** @test */
    public function deleting_a_non_existent_customer_throws_exception()
    {
        $builder = $this->customer();

        $this->assertNull(Customer::find($builder->id));

        $this->expectException(\Exception::class);

        event(new CustomerDeleted($builder->toArray()));
    }

    /** @test */
    public function member_field_kept_as_false_on_active_membership_import()
    {
        $customer = $this->customer();
        $subscription = $this->subscription()->customer($customer)->status('active');

        event(new CustomerImported($customer->toArray()));
        $this->assertFalse(Customer::find($customer->id)->member);

        event(new SubscriptionImported($subscription));

        $this->assertFalse(Customer::find($customer->id)->member);
    }

    /** @test */
    public function member_field_set_kept_as_false_on_inactive_membership_import()
    {
        $customer = $this->customer();
        $subscription = $this->subscription()->customer($customer)->status('paused');

        event(new CustomerImported($customer->toArray()));
        $this->assertFalse(Customer::find($customer->id)->member);

        event(new SubscriptionImported($subscription));

        $this->assertFalse(Customer::find($customer->id)->member);
    }

    /** @test */
    public function member_field_set_to_true_on_membership_activated()
    {
        $customer = $this->customer();

        event(new CustomerImported($customer->toArray()));
        $this->assertFalse(Customer::find($customer->id)->member);

        event(new MembershipActivated($customer->id));

        $this->assertTrue(Customer::find($customer->id)->member);
    }

    /** ignored for now */
    public function membership_activated_with_unknown_customer_throws_exception()
    {
        $customer = $this->customer();

        $this->assertNull(Customer::find($customer->id));

        $this->expectException(\Exception::class);

        event(new MembershipActivated($customer->id));
    }

    /** @test */
    public function member_field_set_to_false_on_membership_deactivated()
    {
        $customer = $this->customer();

        event(new CustomerImported($customer->toArray()));
        Customer::find($customer->id)->save(['member' => true]);

        event(new MembershipDeactivated($customer->id));

        $this->assertFalse(Customer::find($customer->id)->member);
    }

    /** ignored */
    public function membership_deactivated_with_unknown_customer_throws_exception()
    {
        $customer = $this->customer();

        $this->assertNull(Customer::find($customer->id));

        $this->expectException(\Exception::class);

        event(new MembershipDeactivated($customer->id));
    }

    /** @test */
    public function id_checked_field_set_to_false_by_default()
    {
        $builder = $this->customer();

        $this->assertNull(Customer::find($builder->id));

        event(new CustomerCreated($builder->toArray()));

        /** @var Customer $customer */
        $customer = Customer::find($builder->id);

        $this->assertFalse($customer->id_checked);
    }

    /** @test */
    public function id_checked_field_set_to_true_on_id_checked()
    {
        $builder = $this->customer();

        $this->assertNull(Customer::find($builder->id));

        event(new CustomerCreated($builder->toArray()));
        event(new IdWasChecked($builder->id));

        /** @var Customer $customer */
        $customer = Customer::find($builder->id);

        $this->assertTrue($customer->id_checked);
    }
}
