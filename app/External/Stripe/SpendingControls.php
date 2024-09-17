<?php

namespace App\External\Stripe;

use Stripe\StripeObject;

class SpendingControls
{
    private array $spending_limits = [];

    public function stripeObject(): StripeObject
    {
        $stripeObject = new StripeObject();

        if (count($this->spending_limits) > 0) {
            $stripeObject->spending_limits = array_map(fn($sl) => $sl->stripeObject(), $this->spending_limits);
        }

        return $stripeObject;
    }

    public function spending_limits(SpendingLimits ...$limits): SpendingControls
    {
        $this->spending_limits = $limits;

        return $this;
    }
}
