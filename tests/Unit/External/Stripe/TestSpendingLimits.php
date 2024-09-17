<?php

namespace Tests\Unit\External\Stripe;

use App\External\Stripe\SpendingLimits;
use Tests\TestCase;

class TestSpendingLimits extends TestCase
{
    /** @test */
    public function default_spending_limits(): void
    {
        $limits = new SpendingLimits();

        $object = $limits->stripeObject();

        self::assertTrue(isset($object->amount));
        self::assertEquals(500_00, $object->amount);
        self::assertTrue(isset($object->interval));
        self::assertEquals("all_time", $object->interval);
    }

    /** @test */
    public function constructor_spending_limit(): void
    {
        $limits = new SpendingLimits(100_00);

        $object = $limits->stripeObject();

        self::assertTrue(isset($object->amount));
        self::assertEquals(100_00, $object->amount);
        self::assertTrue(isset($object->interval));
        self::assertEquals("all_time", $object->interval);
    }

    /** @test */
    public function daily_interval(): void
    {
        $limits = (new SpendingLimits())->daily();

        $object = $limits->stripeObject();

        self::assertTrue(isset($object->amount));
        self::assertEquals(500_00, $object->amount);
        self::assertTrue(isset($object->interval));
        self::assertEquals("daily", $object->interval);
    }

    /** @test */
    public function weekly_interval(): void
    {
        $limits = (new SpendingLimits())->weekly();

        $object = $limits->stripeObject();

        self::assertTrue(isset($object->amount));
        self::assertEquals(500_00, $object->amount);
        self::assertTrue(isset($object->interval));
        self::assertEquals("weekly", $object->interval);
    }

    /** @test */
    public function monthly_interval(): void
    {
        $limits = (new SpendingLimits())->monthly();

        $object = $limits->stripeObject();

        self::assertTrue(isset($object->amount));
        self::assertEquals(500_00, $object->amount);
        self::assertTrue(isset($object->interval));
        self::assertEquals("monthly", $object->interval);
    }

    /** @test */
    public function yearly_interval(): void
    {
        $limits = (new SpendingLimits())->yearly();

        $object = $limits->stripeObject();

        self::assertTrue(isset($object->amount));
        self::assertEquals(500_00, $object->amount);
        self::assertTrue(isset($object->interval));
        self::assertEquals("yearly", $object->interval);
    }

    /** @test */
    public function per_authorization_interval(): void
    {
        $limits = (new SpendingLimits())->per_authorization();

        $object = $limits->stripeObject();

        self::assertTrue(isset($object->amount));
        self::assertEquals(500_00, $object->amount);
        self::assertTrue(isset($object->interval));
        self::assertEquals("per_authorization", $object->interval);
    }
}
