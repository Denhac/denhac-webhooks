<?php

namespace Tests\Unit\External\Stripe;

use App\External\Stripe\SpendingControls;
use App\External\Stripe\SpendingLimits;
use Tests\TestCase;

class TestSpendingControls extends TestCase
{
    // TODO Add correct logic for setting allowed/blocked categories and merchant countries should we ever need it.

    /** @test */
    public function default_spending_controls(): void
    {
        $controls = new SpendingControls();

        $object = $controls->stripeObject();

        self::assertFalse(isset($object->allowed_categories));
        self::assertFalse(isset($object->allowed_merchant_countries));
        self::assertFalse(isset($object->blocked_categories));
        self::assertFalse(isset($object->blocked_merchant_countries));
        self::assertFalse(isset($object->spending_limits));
    }

    /** @test */
    public function with_spending_limits(): void
    {
        $limits = (new SpendingLimits(100_00))->daily();
        $controls = (new SpendingControls())->spending_limits($limits);

        $object = $controls->stripeObject();

        self::assertFalse(isset($object->allowed_categories));
        self::assertFalse(isset($object->allowed_merchant_countries));
        self::assertFalse(isset($object->blocked_categories));
        self::assertFalse(isset($object->blocked_merchant_countries));
        self::assertTrue(isset($object->spending_limits));
        self::assertEquals([$limits->stripeObject()], $object->spending_limits);
    }

    /** @test */
    public function with_multiple_spending_limits(): void
    {
        $limit_a = (new SpendingLimits(100_00))->daily();
        $limit_b = (new SpendingLimits(300_00))->yearly();
        $controls = (new SpendingControls())->spending_limits($limit_a, $limit_b);

        $object = $controls->stripeObject();

        self::assertFalse(isset($object->allowed_categories));
        self::assertFalse(isset($object->allowed_merchant_countries));
        self::assertFalse(isset($object->blocked_categories));
        self::assertFalse(isset($object->blocked_merchant_countries));
        self::assertTrue(isset($object->spending_limits));
        self::assertEquals([$limit_a->stripeObject(), $limit_b->stripeObject()], $object->spending_limits);
    }
}
