<?php

namespace Database\Factories;

use App\Models\Customer;
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
            // Our ID comes from the post ID in WordPress normally, so we just generate a random one here.
            'id' => $this->faker->numberBetween(),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'username' => $this->faker->userName,
            'id_checked' => false,
            'member' => false,
        ];
    }

    public function member(): static
    {
        return $this->state([
            'member' => true,
        ]);
    }

    public function idChecked(?CustomerFactory $by = null): static
    {
        return $this->state([
            'id_checked' => true,
            'id_was_checked_by_id' => $by ?? Customer::factory(),
        ]);
    }

    public function withSlackId(): static
    {
        return $this->state([
            // Not a perfect representation of slack's User IDs as they use letters and numbers
            'slack_id' => 'U'.($this->faker->numberBetween()),
        ]);
    }
}
