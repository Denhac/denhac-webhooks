<?php

namespace Database\Factories;

use App\External\Stripe\StripeIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->randomNumber(),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'username' => $this->faker->userName,
            'member' => false,  // Several things depend on member status, so we don't randomize it.
        ];
    }

    public function member(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'member' => 'true',
            ];
        });
    }

    public function cardholder(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'stripe_card_holder_id' => StripeIdGenerator::make('ich'),
            ];
        });
    }
}
