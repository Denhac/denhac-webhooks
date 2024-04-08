<?php

namespace Tests\Helpers\Stripe;

use Illuminate\Foundation\Testing\WithFaker;
use Stripe\Issuing\Card;
use Stripe\Issuing\Cardholder;
use Stripe\StripeObject;
use Stripe\Collection;

trait StripeIssuing
{
    use WithFaker;

    private const ID_CHARACTERS = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q',
        'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0',];

    public function stripeId($prefix)
    {
        return $prefix . '_' . implode('', $this->faker->randomElements(self::ID_CHARACTERS, 24, true));
    }

    public function stripeIssuingCard(): Card
    {
        $card = new Card($this->stripeId('ic'));
        $card->cardholder = $this->stripeIssuingCardHolder();
        $card->status = $this->faker->randomElement(['active', 'cancelled', 'inactive']);
        $card->type = $this->faker->randomElement(['physical', 'virtual']);

        return $card;
    }

    public function stripeIssuingCardHolder(): Cardholder {
        $cardHolder = new Cardholder($this->stripeId('ich'));

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
