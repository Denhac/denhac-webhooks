<?php

namespace App\Actions\QuickBooks;

use App\Actions\StaticAction;
use App\Models\Budget;
use Carbon\Carbon;

/**
 *
 */
class PullCurrentlyUsedAmountForBudgetFromQuickBooks
{
    use StaticAction;

    public function execute(Budget $budget): void
    {
        $currentlyUsed = $budget->currently_used;

        /** @var GetAmountSpentByClass $getAmountSpentByClass */
        $getAmountSpentByClass = app(GetAmountSpentByClass::class);

        $today = Carbon::now();  // TODO I don't think there's a timezone bug here, but still need to check
        switch ($budget->type) {
            case Budget::TYPE_ONE_TIME:
            case Budget::TYPE_POOL:
                // Date from before we were using quickbooks to catch everything until now
                $startDate = Carbon::createFromDate(2019, 1, 1);
                $endDate = $today;
                break;
            case Budget::TYPE_RECURRING_MONTHLY:
                $startDate = $today->startOfMonth();
                $endDate = $today->endOfMonth();
                break;
            case Budget::TYPE_RECURRING_YEARLY:
                $startDate = $today->startOfYear();
                $endDate = $today->endOfYear();
                break;
            default:
                throw new \Exception("Unknown budget type $budget->type");
        }

        $quickBooksCurrentlyUsed = $getAmountSpentByClass->execute($budget->quickbooks_class_id, $startDate, $endDate);

        if($budget->type == Budget::TYPE_POOL) {
            // For a pool, the "spend" we just fetched is the negative of the amount we have available to use. i.e if
            // the "amount spent" retrieved above is -700.00 then that means our pool has $700.00 it can use. If our
            // allocated amount is $1,000.00 we can consider that $300.00 used. To make the math easier almost
            // everywhere else, we calculate how much we've "used" based on how much is allocated. The only other place
            // we have to care about this is when updating the allocated_amount field.
            $quickBooksCurrentlyUsed = $budget->allocated_amount + $quickBooksCurrentlyUsed;
        }

        if (abs($quickBooksCurrentlyUsed - $currentlyUsed) < 0.01) {
            $budget->currently_used = $quickBooksCurrentlyUsed;
            $budget->save();
            // TODO Trigger any "go update the cards" stuff here?
        }
    }
}
