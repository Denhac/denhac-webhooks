<?php

namespace Tests\Unit\Projectors;

use App\Models\Customer;
use App\Projectors\WaiverProjector;
use App\StorableEvents\Waiver\WaiverAssignedToCustomer;
use App\Models\Waiver;
use Tests\TestCase;

class WaiverProjectorTest extends TestCase
{
    private Waiver $waiver;

    private Customer $customer;

    public function setUp(): void
    {
        parent::setUp();

        $this->withOnlyEventHandlerType(WaiverProjector::class);

        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $email = $this->faker->email;

        $this->waiver = Waiver::create([
            'waiver_id' => $this->faker->uuid,
            'template_id' => $this->faker->uuid,
            'template_version' => $this->faker->uuid,
            'status' => 'accepted',
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        $wooId = $this->faker->randomNumber();
        $this->customer = Customer::create([
            'id' => $wooId,
            'woo_id' => $wooId,
            'username' => $this->faker->userName,
            'member' => true,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);
    }

    /** @test */
    public function assign_waiver_to_customer_updates_customer_id()
    {
        $this->assertNull($this->waiver->customer_id);

        event(new WaiverAssignedToCustomer($this->waiver->waiver_id, $this->customer->woo_id));

        $this->waiver->refresh();

        $this->assertEquals($this->customer->woo_id, $this->waiver->customer_id);
    }
}
