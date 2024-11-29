<?php

namespace App\External\Stripe;

use Stripe\StripeObject;

class SpendingLimits
{
    private int $amount;
    private string $interval;

    public function __construct(
        $amount = 500_00,  // $500.00
    )
    {
        $this->amount = $amount;
        $this->interval = "all_time";
    }

    public function stripeObject(): StripeObject
    {
        $stripeObject = new StripeObject();

        $stripeObject->amount = $this->amount;
        $stripeObject->interval = $this->interval;

        return $stripeObject;
    }

    public function daily(): SpendingLimits
    {
        $this->interval = "daily";
        return $this;
    }

    public function weekly(): SpendingLimits
    {
        $this->interval = "weekly";
        return $this;
    }

    public function monthly(): SpendingLimits
    {
        $this->interval = "monthly";
        return $this;
    }

    public function yearly(): SpendingLimits
    {
        $this->interval = "yearly";
        return $this;
    }

    public function per_authorization(): SpendingLimits
    {
        $this->interval = "per_authorization";
        return $this;
    }
}
