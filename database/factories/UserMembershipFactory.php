<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\UserMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserMembership>
 */
class UserMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Our ID comes from the post ID in WordPress normally, so we just generate a random one here.
            'id' => $this->faker->numberBetween(),
            'customer_id' => Customer::factory(),
            'plan_id' => UserMembership::MEMBERSHIP_FULL_MEMBER,
            'status' => 'active',
        ];
    }

    public function paused(): static
    {
        return $this->state([
            'status' => 'paused',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
        ]);
    }
}
