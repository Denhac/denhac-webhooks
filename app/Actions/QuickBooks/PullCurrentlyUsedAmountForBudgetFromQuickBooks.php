<?php

namespace App\Actions\QuickBooks;

use App\Actions\StaticAction;
use App\Models\Budget;
use Carbon\Carbon;

/**
 * Runs a QuickBook report for a specific budget to determine if the internal model needs to be updated. This can be on
 * notification that something has changed (via webhook) or just run at a set time every day against all budgets. If
 * the currently used amount doesn't need to be updated, nothing further will be done. If it does need to be updated,
 * we queue a job to update the spendable amounts for the affected cards.
 */
class PullCurrentlyUsedAmountForBudgetFromQuickBooks
{
    use StaticAction;

    public function execute(Budget $budget): void
    {
        $currentlyUsed = $budget->currently_used;

        /** @var GetAmountSpentByClass $getAmountSpentByClass */
        $getAmountSpentByClass = app(GetAmountSpentByClass::class);

        // TODO I don't think there's a timezone bug here, but still need to check
        // Some things suggest it's Pacific time and others suggest that it's local to the QBO install. We'd need a
        // transaction that would be in one day if it was pacific and in another if it was local to test.
        $today = Carbon::today();

        switch ($budget->type) {
            case Budget::TYPE_ONE_TIME:
            case Budget::TYPE_POOL:
                // Date from before we were using quickbooks to catch everything until now
                $startDate = Carbon::create(2019);
                $endDate = $today->copy()->endOfDay();
                break;
            case Budget::TYPE_RECURRING_MONTHLY:
                $startDate = $today->copy()->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
                break;
            case Budget::TYPE_RECURRING_YEARLY:
                $startDate = $today->copy()->startOfYear();
                $endDate = $today->copy()->endOfYear();
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

            if($quickBooksCurrentlyUsed < 0) {
                // This can happen if we end up collecting over our pool, but we haven't shifted the excess out of this
                // budget class yet.
                $quickBooksCurrentlyUsed = 0;
            }
        }

        if (abs($quickBooksCurrentlyUsed - $currentlyUsed) > 0.01) {
            $budget->currently_used = $quickBooksCurrentlyUsed;
            $budget->save();
        }
    }
}
