<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainableEquipment>
 */
class TrainableEquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $fake->unique()->words(2),
            'user_plan_id' => $fake->unique()->numberBetween(100000, 200000),
            'trainer_plan_id' => $fake->unique()->numberBetween(300000, 400000),
        ];
    }
}
