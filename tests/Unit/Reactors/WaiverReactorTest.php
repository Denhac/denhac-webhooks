<?php

namespace Tests\Unit\Reactors;

use App\Aggregates\MembershipAggregate;
use App\Customer;
use App\Reactors\WaiverReactor;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\CustomerDeleted;
use App\StorableEvents\CustomerImported;
use App\StorableEvents\CustomerUpdated;
use App\StorableEvents\WaiverAccepted;
use App\StorableEvents\WaiverAssignedToCustomer;
use App\Waiver;
use Tests\AssertsActions;
use Tests\TestCase;

/**
 * Our waiver matching tests start with a base customer and waiver that match. Then they change attributes to verify
 * that all attributes we care about must match. We also delete the customer when looking for a mismatch because if our
 * code is matching and just matches on say the first user, our tests would pass but our code would be wrong.
 */
class WaiverReactorTest extends TestCase
{
    use AssertsActions;

    private string $firstName;

    private string $lastName;

    private string $email;

    private Customer $matchingCustomer;

    private Waiver $matchingWaiver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(WaiverReactor::class);

        $this->firstName = $this->faker->firstName;
        $this->lastName = $this->faker->lastName;
        $this->email = $this->faker->email;

        $this->matchingWaiver = Waiver::create([
            'waiver_id' => $this->faker->uuid,
            'template_id' => $this->faker->uuid,
            'template_version' => $this->faker->uuid,
            'status' => 'accepted',
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
        ]);

        $this->matchingCustomer = Customer::create([
            'username' => $this->faker->userName,
            'woo_id' => $this->faker->randomNumber(),
            'member' => true,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
        ]);
    }

    /** @test */
    public function waiver_accepted_with_all_fields_matching_is_assigned_to_customer()
    {
        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied()
            ->assertNothingRecorded();

        event(new WaiverAccepted($this->waiver()->id($this->matchingWaiver->waiver_id)->toArray()));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertApplied([
                new WaiverAssignedToCustomer(
                    $this->matchingWaiver->waiver_id,
                    $this->matchingCustomer->woo_id,
                ),
            ]);
    }

    /** @test */
    public function waiver_accepted_with_different_first_name_is_not_assigned_to_customer()
    {
        $customer = Customer::create([
            'username' => $this->matchingCustomer->username,
            'woo_id' => $this->matchingCustomer->woo_id,
            'member' => $this->matchingCustomer->member,
            'last_name' => $this->matchingCustomer->last_name,
            'email' => $this->matchingCustomer->email,

            'first_name' => $this->faker->firstName,
        ]);

        $this->assertNotEquals($customer->first_name, $this->matchingCustomer->first_name);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($customer)
            ->assertNothingApplied();

        event(new WaiverAccepted($this->waiver()->id($this->matchingWaiver->waiver_id)->toArray()));

        MembershipAggregate::fakeCustomer($customer)
            ->assertNothingApplied();
    }

    /** @test */
    public function waiver_accepted_with_different_last_name_is_not_assigned_to_customer()
    {
        $customer = Customer::create([
            'username' => $this->matchingCustomer->username,
            'woo_id' => $this->matchingCustomer->woo_id,
            'member' => $this->matchingCustomer->member,
            'first_name' => $this->matchingCustomer->first_name,
            'email' => $this->matchingCustomer->email,

            'last_name' => $this->faker->lastName,
        ]);

        $this->assertNotEquals($customer->last_name, $this->matchingCustomer->last_name);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($customer)
            ->assertNothingApplied();

        event(new WaiverAccepted($this->waiver()->id($this->matchingWaiver->waiver_id)->toArray()));

        MembershipAggregate::fakeCustomer($customer)
            ->assertNothingApplied();
    }

    /** @test */
    public function waiver_accepted_with_different_email_is_not_assigned_to_customer()
    {
        $customer = Customer::create([
            'username' => $this->matchingCustomer->username,
            'woo_id' => $this->matchingCustomer->woo_id,
            'member' => $this->matchingCustomer->member,
            'first_name' => $this->matchingCustomer->first_name,
            'last_name' => $this->matchingCustomer->last_name,

            'email' => $this->faker->email,
        ]);

        $this->assertNotEquals($customer->email, $this->matchingCustomer->email);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($customer)
            ->assertNothingApplied()
            ->assertNothingRecorded();

        event(new WaiverAccepted($this->waiver()->id($this->matchingWaiver->waiver_id)->toArray()));

        MembershipAggregate::fakeCustomer($customer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_created_with_all_fields_matching_is_assigned_to_customer()
    {
        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertApplied([
                new WaiverAssignedToCustomer(
                    $this->matchingWaiver->waiver_id,
                    $this->matchingCustomer->woo_id,
                ),
            ]);
    }

    /** @test */
    public function customer_created_with_different_first_name_is_not_assigned_to_customer()
    {
        $firstName = $this->faker->firstName;
        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($firstName)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        $this->assertNotEquals($firstName, $this->matchingCustomer->first_name);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_created_with_different_last_name_is_not_assigned_to_customer()
    {
        $lastName = $this->faker->lastName;
        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($lastName)
                ->email($this->matchingCustomer->email)
        ));

        $this->assertNotEquals($lastName, $this->matchingCustomer->last_name);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_created_with_different_email_is_not_assigned_to_customer()
    {
        $email = $this->faker->email;
        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($email)
        ));

        $this->assertNotEquals($email, $this->matchingCustomer->email);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_created_with_all_fields_matching_is_not_reassigned_to_customer()
    {
        $this->matchingWaiver->customer_id = $this->matchingCustomer->woo_id;
        $this->matchingWaiver->save();

        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_updated_with_all_fields_matching_is_assigned_to_customer()
    {
        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertApplied([
                new WaiverAssignedToCustomer(
                    $this->matchingWaiver->waiver_id,
                    $this->matchingCustomer->woo_id,
                ),
            ]);
    }

    /** @test */
    public function customer_updated_with_different_first_name_is_not_assigned_to_customer()
    {
        $firstName = $this->faker->firstName;
        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($firstName)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        $this->assertNotEquals($firstName, $this->matchingCustomer->first_name);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_updated_with_different_last_name_is_not_assigned_to_customer()
    {
        $lastName = $this->faker->lastName;
        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($lastName)
                ->email($this->matchingCustomer->email)
        ));

        $this->assertNotEquals($lastName, $this->matchingCustomer->last_name);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_updated_with_different_email_is_not_assigned_to_customer()
    {
        $email = $this->faker->email;
        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($email)
        ));

        $this->assertNotEquals($email, $this->matchingCustomer->email);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_updated_with_all_fields_matching_is_not_reassigned_to_customer()
    {
        $this->matchingWaiver->customer_id = $this->matchingCustomer->woo_id;
        $this->matchingWaiver->save();

        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_imported_with_all_fields_matching_is_assigned_to_customer()
    {
        event(new CustomerImported(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertApplied([
                new WaiverAssignedToCustomer(
                    $this->matchingWaiver->waiver_id,
                    $this->matchingCustomer->woo_id,
                ),
            ]);
    }

    /** @test */
    public function customer_imported_with_different_first_name_is_not_assigned_to_customer()
    {
        $firstName = $this->faker->firstName;
        event(new CustomerImported(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($firstName)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        $this->assertNotEquals($firstName, $this->matchingCustomer->first_name);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_imported_with_different_last_name_is_not_assigned_to_customer()
    {
        $lastName = $this->faker->lastName;
        event(new CustomerImported(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($lastName)
                ->email($this->matchingCustomer->email)
        ));

        $this->assertNotEquals($lastName, $this->matchingCustomer->last_name);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_imported_with_different_email_is_not_assigned_to_customer()
    {
        $email = $this->faker->email;
        event(new CustomerImported(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($email)
        ));

        $this->assertNotEquals($email, $this->matchingCustomer->email);

        $this->matchingCustomer->delete();

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_deleted_with_all_fields_matching_is_not_reassigned_to_customer()
    {
        $this->matchingWaiver->customer_id = $this->matchingCustomer->woo_id;
        $this->matchingWaiver->save();

        event(new CustomerDeleted(
            $this->customer()
                ->id($this->matchingCustomer->woo_id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }
}
