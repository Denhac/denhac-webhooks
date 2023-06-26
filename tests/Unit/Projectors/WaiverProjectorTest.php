<?php

namespace Tests\Unit\Projectors;


use App\Customer;
use App\Printer3D;
use App\Projectors\PrinterProjector;
use App\Projectors\WaiverProjector;
use App\StorableEvents\OctoPrintStatusUpdated;
use App\StorableEvents\WaiverAssignedToCustomer;
use App\Waiver;
use Carbon\Carbon;
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

        $this->customer = Customer::create([
            'username' => $this->faker->userName,
            'woo_id' => $this->faker->randomNumber(),
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
