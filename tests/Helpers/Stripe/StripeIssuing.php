<?php

namespace Tests\Helpers\Stripe;

use App\External\Stripe\StripeIdGenerator;
use Illuminate\Foundation\Testing\WithFaker;
use Stripe\Issuing\Card;
use Stripe\Issuing\Cardholder;
use Stripe\StripeObject;
use Stripe\Collection;

trait StripeIssuing
{
    use WithFaker;

    public function stripeIssuingCard(): Card
    {
        $card = new Card(StripeIdGenerator::make('ic'));
        $card->cardholder = $this->stripeIssuingCardHolder();
        $card->status = $this->faker->randomElement(['active', 'cancelled', 'inactive']);
        $card->type = $this->faker->randomElement(['physical', 'virtual']);

        return $card;
    }

    public function stripeIssuingCardHolder(): Cardholder
    {
        $cardHolder = new Cardholder(StripeIdGenerator::make('ich'));
        $cardHolder->phone_number = $this->faker->phoneNumber();

        return $cardHolder;
    }

    public function stripeCollection(StripeObject ...$objects): Collection
    {
        return Collection::constructFrom([
            'object' => 'list',
            'data' => $objects,
            'has_more' => false,
        ], []);
    }
}
