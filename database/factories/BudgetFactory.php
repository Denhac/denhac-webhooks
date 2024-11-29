<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $allocatedAmount = $this->faker->randomFloat(2, 500);  // Start at allocated $500
        $currentlyUsed = $this->faker->randomFloat(2, 0, 500);  // Used max $500

        return [
            'quickbooks_class_id' => $this->faker->numerify('###################'),
            'name' => $this->faker->name,
            'type' => $this->faker->randomElement([
                Budget::TYPE_ONE_TIME,
                Budget::TYPE_RECURRING_MONTHLY,
                Budget::TYPE_RECURRING_YEARLY,
                Budget::TYPE_POOL,
            ]),
            'active' => true,
            'allocated_amount' => $allocatedAmount,
            'currently_used' => $currentlyUsed,
        ];
    }

    public function owner(Customer $owner): static
    {
        return $this->state(function (array $attributes) use ($owner) {
            return [
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $owner->getKey(),
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => false,
            ];
        });
    }

    public function one_time(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => Budget::TYPE_ONE_TIME,
            ];
        });
    }

    public function monthly(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => Budget::TYPE_RECURRING_MONTHLY,
            ];
        });
    }

    public function yearly(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => Budget::TYPE_RECURRING_YEARLY,
            ];
        });
    }

    public function pool(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => Budget::TYPE_POOL,
            ];
        });
    }
}
