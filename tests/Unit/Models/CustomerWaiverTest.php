<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Waiver;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CustomerWaiverTest extends TestCase
{
    use WithFaker;

    private Waiver $validMembershipWaiver;

    private Waiver $someOtherWaiver;

    protected function setUp(): void
    {
        parent::setUp();

        // The only thing that makes this one valid and the next one not is the config setting below.
        $this->validMembershipWaiver = Waiver::create([
            'waiver_id' => $this->faker->uuid,
            'template_id' => $this->faker->uuid,
            'template_version' => $this->faker->uuid,
            'status' => 'accepted',
            'email' => $this->faker->email,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ]);
        $this->someOtherWaiver = Waiver::create([
            'waiver_id' => $this->faker->uuid,
            'template_id' => $this->faker->uuid,
            'template_version' => $this->faker->uuid,
            'status' => 'accepted',
            'email' => $this->faker->email,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ]);

        Config::set('denhac.waiver.membership_waiver_template_id', $this->validMembershipWaiver->template_id);
        Config::set('denhac.waiver.membership_waiver_template_version', $this->validMembershipWaiver->template_version);
    }

    /** @test */
    public function customer_who_signed_good_waiver_returns_true(): void
    {
        /** @var Customer $customer */
        $wooId = $this->faker->randomNumber();
        $customer = Customer::create([
            'id' => $wooId,
            'woo_id' => $wooId,
            'username' => $this->faker->userName,
            'member' => true,
            'email' => $this->faker->email,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ]);

        $this->validMembershipWaiver->customer_id = $customer->woo_id;
        $this->validMembershipWaiver->save();
        $customer->refresh();

        $this->assertTrue($customer->hasSignedMembershipWaiver());
    }

    /** @test */
    public function customer_who_signed_other_waiver_returns_false(): void
    {
        /** @var Customer $customer */
        $wooId = $this->faker->randomNumber();
        $customer = Customer::create([
            'id' => $wooId,
            'woo_id' => $wooId,
            'username' => $this->faker->userName,
            'member' => true,
            'email' => $this->faker->email,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ]);

        $this->someOtherWaiver->customer_id = $customer->woo_id;
        $this->someOtherWaiver->save();
        $customer->refresh();

        $this->assertFalse($customer->hasSignedMembershipWaiver());
    }

    /** @test */
    public function customer_get_waiver_url(): void
    {
        /** @var Customer $customer */
        $wooId = $this->faker->randomNumber();
        $customer = Customer::create([
            'id' => $wooId,
            'woo_id' => $wooId,
            'username' => $this->faker->userName,
            'member' => true,
            'email' => $this->faker->email,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ]);

        $this->assertEquals(
            "https://app.waiverforever.com/pending/{$this->validMembershipWaiver->template_id}?name-first_name-2={$customer->first_name}&name-last_name-2={$customer->last_name}&email-email-3={$customer->email}&checkbox-checked-4=true",
            $customer->getWaiverUrl()
        );
    }
}
