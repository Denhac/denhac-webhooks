<?php

namespace Tests\Unit\Models;

use App\Models\Budget;
use App\Models\Customer;
use Database\Factories\BudgetFactory;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    private Customer $customer;

    private Budget $oneTimeBudget;
    private Budget $monthlyBudget;
    private Budget $yearlyBudget;
    private Budget $poolBudget;

    private const ALLOCATED_AMOUNT = 500;
    private const CURRENTLY_USED = 200;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oneTimeBudget = $this->makeBudget()->one_time()->create();
        $this->monthlyBudget = $this->makeBudget()->monthly()->create();
        $this->yearlyBudget = $this->makeBudget()->yearly()->create();
        $this->poolBudget = $this->makeBudget()->pool()->create();
    }

    private function makeBudget(): BudgetFactory
    {
        if (empty($this->customer)) {
            $this->customer = Customer::factory()->create();
        }

        /** @var BudgetFactory $factory */
        $factory = Budget::factory();
        return $factory
            ->owner($this->customer)
            ->state([
                'allocated_amount' => self::ALLOCATED_AMOUNT,
                'currently_used' => self::CURRENTLY_USED,
            ]);
    }

    /** @test */
    public function budget_available_with_no_extra(): void
    {
        Budget::setExtraBufferAmount(0);

        $expectedAvailable = self::ALLOCATED_AMOUNT - self::CURRENTLY_USED;
        self::assertEquals($expectedAvailable, $this->oneTimeBudget->available_to_spend);
        self::assertEquals($expectedAvailable, $this->monthlyBudget->available_to_spend);
        self::assertEquals($expectedAvailable, $this->yearlyBudget->available_to_spend);
        self::assertEquals($expectedAvailable, $this->poolBudget->available_to_spend);
    }

    /** @test */
    public function budget_available_with_extra(): void
    {
        Budget::setExtraBufferAmount(10);

        $expectedAvailable = self::ALLOCATED_AMOUNT - self::CURRENTLY_USED;
        $expectedAvailableWithExtra = $expectedAvailable + 10;
        self::assertEquals($expectedAvailableWithExtra, $this->oneTimeBudget->available_to_spend);
        self::assertEquals($expectedAvailableWithExtra, $this->monthlyBudget->available_to_spend);
        self::assertEquals($expectedAvailableWithExtra, $this->yearlyBudget->available_to_spend);
        self::assertEquals($expectedAvailable, $this->poolBudget->available_to_spend);
    }
}
