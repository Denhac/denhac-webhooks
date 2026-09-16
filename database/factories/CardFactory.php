<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Card numbers are 1-65535, printed as five digits on the card face.
            // Unique so a test staging more than one card can tell them apart.
            'number' => (string) $this->faker->unique()->numberBetween(1, 65535),
            'active' => false,
            'member_has_card' => true,
            'customer_id' => Customer::factory(),
        ];
    }

    /**
     * We believe WinDSX will open a door for this card.
     */
    public function active(): static
    {
        return $this->state([
            'active' => true,
        ]);
    }

    /**
     * The card is no longer on the member's profile.
     */
    public function returned(): static
    {
        return $this->state([
            'member_has_card' => false,
        ]);
    }
}
