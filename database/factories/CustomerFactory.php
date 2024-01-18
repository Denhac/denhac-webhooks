<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\UserMembership;

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
            // unique() doesn't know about our manually created users, so we reserve 0-99 for those.
            'id' => fake()->unique()->numberBetween(100),
            'email' => fake()->unique()->safeEmail(),
            'username' => Str::random(10),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'member' => fake()->boolean(),
            'id_checked' => fake()->boolean()
        ];
    }
}
