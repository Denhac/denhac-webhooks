<?php

namespace App\Actions\Stripe;

use App\Models\StripeCard;
use Spatie\QueueableAction\QueueableAction;
use Stripe\Issuing\Card;
use Stripe\StripeClient;

class UpdateStripeCardsFromSource
{
    use QueueableAction;

    private StripeClient $stripeClient;

    public function __construct(StripeClient $stripeClient)
    {
        $this->stripeClient = $stripeClient;
    }

    public function execute()
    {
        $cards = $this->stripeClient->issuing->cards->all()->autoPagingIterator();

        $stripeModels = StripeCard::all();

        foreach ($cards as $card) {
            /** @var Card $card */
            $cardModel = $stripeModels->firstWhere('id', $card->id);

            if (is_null($cardModel)) {
                $cardModel = StripeCard::make([
                    'id' => $card->id,
                    'cardholder_id' => $card->cardholder->id,
                ]);
            }

            $cardModel->type = $card->type;
            $cardModel->status = $card->status;

            $cardModel->save();
        }
    }
}
