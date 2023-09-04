<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\TrainableEquipment;
use App\Models\UserMembership;
use Tests\TestCase;

class CustomerTrainerTest extends TestCase
{
    private const TRAINER_PLAN_ID = 1234;

    private const USER_PLAN_ID = 5678;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Customer $customer */
        $this->customer = Customer::create([
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'username' => $this->faker->userName,
            'email' => $this->faker->email,
            'woo_id' => 1,
            'member' => true,
        ]);

        TrainableEquipment::create([
            'name' => 'Test Equipment',
            'user_plan_id' => self::USER_PLAN_ID,
            'trainer_plan_id' => self::TRAINER_PLAN_ID,
        ]);
    }

    /** @test */
    public function is_a_trainer_returns_false_if_not_trained_on_anything()
    {
        $this->assertFalse($this->customer->isATrainer());
    }

    /** @test */
    public function is_a_trainer_returns_false_if_only_user()
    {
        UserMembership::create([
            'id' => $this->faker->randomNumber(),
            'plan_id' => self::USER_PLAN_ID,
            'status' => 'active',
            'customer_id' => $this->customer->id,
        ]);

        $this->assertFalse($this->customer->isATrainer());
    }

    /** @test */
    public function is_a_trainer_returns_false_if_no_active_membership()
    {
        UserMembership::create([
            'id' => $this->faker->randomNumber(),
            'plan_id' => self::TRAINER_PLAN_ID,
            'status' => 'cancelled',
            'customer_id' => $this->customer->id,
        ]);

        $this->assertFalse($this->customer->isATrainer());
    }

    /** @test */
    public function is_a_trainer_returns_false_if_a_trainer()
    {
        UserMembership::create([
            'id' => $this->faker->randomNumber(),
            'plan_id' => self::TRAINER_PLAN_ID,
            'status' => 'active',
            'customer_id' => $this->customer->id,
        ]);

        $this->assertTrue($this->customer->isATrainer());
    }
}
