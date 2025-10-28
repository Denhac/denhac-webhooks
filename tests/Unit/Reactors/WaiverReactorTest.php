<?php

namespace Tests\Unit\Reactors;

use App\Aggregates\MembershipAggregate;
use App\Models\Customer;
use App\Models\Waiver;
use App\Reactors\WaiverReactor;
use App\StorableEvents\Waiver\WaiverAccepted;
use App\StorableEvents\Waiver\WaiverAssignedToCustomer;
use App\StorableEvents\WooCommerce\CustomerCreated;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\CustomerImported;
use App\StorableEvents\WooCommerce\CustomerUpdated;
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

        $this->firstName = $this->faker->firstName();
        $this->lastName = $this->faker->lastName();
        $this->email = $this->faker->email();

        $this->matchingWaiver = Waiver::create([
            'waiver_id' => $this->faker->uuid(),
            'template_id' => $this->faker->uuid(),
            'template_version' => $this->faker->uuid(),
            'status' => 'accepted',
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
        ]);

        $this->matchingCustomer = Customer::create([
            'id' => $this->faker->numberBetween(1),
            'username' => $this->faker->userName(),
            'member' => true,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
        ]);
    }

    protected function uniqueFirstName(): string {
        while(true) {
            $firstName = $this->faker->firstName();
            if($firstName != $this->matchingCustomer->first_name) {
                return $firstName;
            }
        }
    }

    protected function uniqueLastName(): string {
        while(true) {
            $firstName = $this->faker->firstName();
            if($firstName != $this->matchingCustomer->first_name) {
                return $firstName;
            }
        }
    }

    protected function uniqueEmail(): string {
        while(true) {
            $firstName = $this->faker->firstName();
            if($firstName != $this->matchingCustomer->first_name) {
                return $firstName;
            }
        }
    }


    /** @test */
    public function waiver_accepted_with_all_fields_matching_is_assigned_to_customer(): void
    {
        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied()
            ->assertNothingRecorded();

        event(new WaiverAccepted($this->waiver()->id($this->matchingWaiver->waiver_id)->toArray()));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertApplied([
                new WaiverAssignedToCustomer(
                    $this->matchingWaiver->waiver_id,
                    $this->matchingCustomer->id,
                ),
            ]);
    }

    /** @test */
    public function waiver_accepted_with_different_first_name_is_not_assigned_to_customer(): void
    {
        $customer = Customer::create([
            'id' => $this->faker->randomNumber(),
            'username' => $this->matchingCustomer->username,
            'member' => $this->matchingCustomer->member,
            'last_name' => $this->matchingCustomer->last_name,
            'email' => $this->matchingCustomer->email,

            'first_name' => $this->uniqueFirstName(),
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
    public function waiver_accepted_with_different_last_name_is_not_assigned_to_customer(): void
    {
        $customer = Customer::create([
            'id' => $this->faker->randomNumber(),
            'username' => $this->matchingCustomer->username,
            'member' => $this->matchingCustomer->member,
            'first_name' => $this->matchingCustomer->first_name,
            'email' => $this->matchingCustomer->email,

            'last_name' => $this->uniqueLastName(),
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
    public function waiver_accepted_with_different_email_is_not_assigned_to_customer(): void
    {
        $customer = Customer::create([
            'id' => $this->faker->randomNumber(),
            'username' => $this->matchingCustomer->username,
            'member' => $this->matchingCustomer->member,
            'first_name' => $this->matchingCustomer->first_name,
            'last_name' => $this->matchingCustomer->last_name,

            'email' => $this->uniqueEmail(),
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
    public function customer_created_with_all_fields_matching_is_assigned_to_customer(): void
    {
        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertApplied([
                new WaiverAssignedToCustomer(
                    $this->matchingWaiver->waiver_id,
                    $this->matchingCustomer->id,
                ),
            ]);
    }

    /** @test */
    public function customer_created_with_different_first_name_is_not_assigned_to_customer(): void
    {
        $firstName = $this->uniqueFirstName();
        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_created_with_different_last_name_is_not_assigned_to_customer(): void
    {
        $lastName = $this->uniqueLastName();
        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_created_with_different_email_is_not_assigned_to_customer(): void
    {
        $email = $this->uniqueEmail();
        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_created_with_all_fields_matching_is_not_reassigned_to_customer(): void
    {
        $this->matchingWaiver->customer_id = $this->matchingCustomer->id;
        $this->matchingWaiver->save();

        event(new CustomerCreated(
            $this->customer()
                ->id($this->matchingCustomer->id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_updated_with_all_fields_matching_is_assigned_to_customer(): void
    {
        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertApplied([
                new WaiverAssignedToCustomer(
                    $this->matchingWaiver->waiver_id,
                    $this->matchingCustomer->id,
                ),
            ]);
    }

    /** @test */
    public function customer_updated_with_different_first_name_is_not_assigned_to_customer(): void
    {
        $firstName = $this->uniqueFirstName();
        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_updated_with_different_last_name_is_not_assigned_to_customer(): void
    {
        $lastName = $this->uniqueLastName();
        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_updated_with_different_email_is_not_assigned_to_customer(): void
    {
        $email = $this->uniqueEmail();
        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_updated_with_all_fields_matching_is_not_reassigned_to_customer(): void
    {
        $this->matchingWaiver->customer_id = $this->matchingCustomer->id;
        $this->matchingWaiver->save();

        event(new CustomerUpdated(
            $this->customer()
                ->id($this->matchingCustomer->id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }

    /** @test */
    public function customer_imported_with_all_fields_matching_is_assigned_to_customer(): void
    {
        event(new CustomerImported(
            $this->customer()
                ->id($this->matchingCustomer->id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertApplied([
                new WaiverAssignedToCustomer(
                    $this->matchingWaiver->waiver_id,
                    $this->matchingCustomer->id,
                ),
            ]);
    }

    /** @test */
    public function customer_imported_with_different_first_name_is_not_assigned_to_customer(): void
    {
        $firstName = $this->uniqueFirstName();
        event(new CustomerImported(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_imported_with_different_last_name_is_not_assigned_to_customer(): void
    {
        $lastName = $this->uniqueLastName();
        event(new CustomerImported(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_imported_with_different_email_is_not_assigned_to_customer(): void
    {
        $email = $this->uniqueEmail();
        event(new CustomerImported(
            $this->customer()
                ->id($this->matchingCustomer->id)
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
    public function customer_deleted_with_all_fields_matching_is_not_reassigned_to_customer(): void
    {
        $this->matchingWaiver->customer_id = $this->matchingCustomer->id;
        $this->matchingWaiver->save();

        event(new CustomerDeleted(
            $this->customer()
                ->id($this->matchingCustomer->id)
                ->first_name($this->matchingCustomer->first_name)
                ->last_name($this->matchingCustomer->last_name)
                ->email($this->matchingCustomer->email)
        ));

        MembershipAggregate::fakeCustomer($this->matchingCustomer)
            ->assertNothingApplied();
    }
}
