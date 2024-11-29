<?php

namespace App\Actions\Stripe;

use App\External\Stripe\SpendingControls;
use App\Models\StripeCard;
use Spatie\QueueableAction\QueueableAction;
use Stripe\StripeClient;

class UpdateSpendingLimitsOnCard
{
    use QueueableAction;

    private StripeClient $client;

    public function __construct(StripeClient $client)
    {
        $this->client = $client;
    }

    public function execute(StripeCard $stripeCard, SpendingControls $controls)
    {
        return $this->client->issuing->cards
            ->update($stripeCard->id,
                [
                    'spending_controls' => $controls->stripeObject()->toArray(),
                ]
            );
    }
}
