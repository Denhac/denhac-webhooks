<?php

namespace Database\Factories;

use App\External\Stripe\StripeIdGenerator;
use App\Models\Customer;
use App\Models\StripeCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StripeCard>
 */
class StripeCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => StripeIdGenerator::make('ic'),
            'type' => StripeCard::TYPE_PHYSICAL,
            'status' => StripeCard::STATUS_INACTIVE,
        ];
    }

    public function cardholder(Customer $customer)
    {
        if(empty($customer->stripe_card_holder_id)) {
            throw new \Exception("Stripe Card Holder id for customer was empty");
        }
        return $this->state(function (array $attributes) use($customer) {
            return [
                'cardholder_id' => $customer->stripe_card_holder_id,
            ];
        });
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => StripeCard::STATUS_ACTIVE,
            ];
        });
    }

    public function canceled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => StripeCard::STATUS_CANCELED,
            ];
        });
    }

    public function virtual(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => StripeCard::TYPE_VIRTUAL,
            ];
        });
    }
}
