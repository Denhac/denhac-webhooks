<?php

namespace App\Actions\Stripe;

use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;
use Stripe\StripeClient;
use Stripe\Topup;

class SetIssuingBalanceToValue
{
    use QueueableAction;

    private StripeClient $stripeClient;

    public function __construct(StripeClient $stripeClient)
    {
        $this->stripeClient = $stripeClient;
    }

    public function execute($amountInPennies, $message)
    {
        $currentBalance = $this->stripeClient->balance->retrieve()->issuing->available[0]->amount;
        $pendingTopUps = collect($this->stripeClient->topups->all(['status' => 'pending'])->data);
        $pendingAmount = $pendingTopUps->where('destination_balance', 'issuing')->sum(fn($tu) => $tu->amount);

        $expectedBalance = $currentBalance + $pendingAmount;

        if ($expectedBalance >= $amountInPennies) {
            return;  // We'll eventually hit that amount with our current pending transactions, or we'll exceed it.
        }

        $moneyNeeded = $amountInPennies - $expectedBalance;

        $topUpParams = [
            "currency" => "usd",
            "amount" => $moneyNeeded,
            "description" => $message,
            "destination_balance" => "issuing",
            "statement_descriptor" => "Stripe Issuing Top-up",
        ];

        $topUp = $this->stripeClient->topups->create($topUpParams);
        Log::info("Created Top-Up for $moneyNeeded to reach $amountInPennies: $topUp->id");
    }
}
