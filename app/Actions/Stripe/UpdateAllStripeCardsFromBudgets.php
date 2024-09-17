<?php

namespace App\Actions\Stripe;

use App\External\Stripe\SpendingControls;
use App\External\Stripe\SpendingLimits;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\StripeCard;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\QueueableAction\QueueableAction;

class UpdateAllStripeCardsFromBudgets
{
    use QueueableAction;

    public function __construct()
    {

    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        /** @var UpdateSpendingLimitsOnCard $updateSpendingLimitsOnCard */
        $updateSpendingLimitsOnCard = app(UpdateSpendingLimitsOnCard::class);

        $cardsToSpendingLimit = collect();
        $issuingBalance = 0;

        foreach (Budget::all() as $budget) {
            /** @var Budget $budget */
            if(! $budget->active) {
                // This budget isn't active, so we ignore it for all top-up and spending limit purposes. Any cards that
                // are only attached to this budget will have their spending limit set to a penny.
                continue;
            }

            $cards = $this->getCardsThatCanSpend($budget);

            $budgetAvailable = $budget->available_to_spend;

            foreach($cards as $stripeCard) {
                $currentAmount = $cardsToSpendingLimit->get($stripeCard->id, 0);
                $currentAmount += $budgetAvailable;
                $cardsToSpendingLimit->put($stripeCard->id, $currentAmount);
            }

            if($cards->isNotEmpty()) {
                $issuingBalance += $budgetAvailable;
            }
        }

        foreach(StripeCard::all() as $stripeCard) {
            if($stripeCard->status == StripeCard::STATUS_CANCELED) {
                // Canceled is permanent, spending limits don't matter at all.
                continue;
            }

            // $0.01 since we can't set it to 0. Effectively disables the card if no spend is available.
            $spendingLimit = $cardsToSpendingLimit->get($stripeCard->id, 0.01);
            $spendingLimitInPennies = round($spendingLimit * 100);

            $limits = (new SpendingLimits($spendingLimitInPennies))->per_authorization();
            $controls = (new SpendingControls())->spending_limits($limits);
            $updateSpendingLimitsOnCard->onQueue()->execute($stripeCard, $controls);
        }

        $issuingBalanceInPennies = round($issuingBalance * 100);

        if($issuingBalanceInPennies > 0) {
            /** @var SetIssuingBalanceToValue $setIssuingBalanceToValue */
            $setIssuingBalanceToValue = app(SetIssuingBalanceToValue::class);
            $today = Carbon::today('America/Denver');
            $message = "Top-Up to match current outstanding budgets {$today->toFormattedDayDateString()}";
            $setIssuingBalanceToValue->onQueue()->execute($issuingBalanceInPennies, $message);
        }
    }

    /**
     * @param Budget $budget
     * @return Collection<StripeCard>
     * @throws \Exception
     */
    private function getCardsThatCanSpend(Budget $budget): Collection
    {
        $owner = $budget->owner;

        if(is_a($owner, Customer::class)) {
            if(! $owner->member) {
                // Doesn't matter if they have a physical card or not, we'll pretend they don't so any physical cards
                // they do have get set to a spending limit of a penny.
                return collect();
            }

            if(is_null($owner->stripe_card_holder_id)) {
                // This person might have a card, but the card holder in stripe hasn't been associated to their account
                // just yet. We limit their spending to be safe.
                return collect();
            }

            $stripeCard = StripeCard::where('cardholder_id', $owner->stripe_card_holder_id)
                ->where('status', StripeCard::STATUS_ACTIVE)
                ->where('type', StripeCard::TYPE_PHYSICAL)
                ->first();

            if(is_null($stripeCard)) {
                return collect();
            }

            return collect([$stripeCard]);

        } else {
            $owner_type = get_class($owner);
            throw new \Exception("Unknown owner type: {$owner_type}");
        }
    }
}
