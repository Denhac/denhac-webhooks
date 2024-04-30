<?php

namespace Tests\Unit\Actions\Stripe;

use App\Actions\Stripe\SetIssuingBalanceToValue;
use App\Actions\Stripe\UpdateAllStripeCardsFromBudgets;
use App\Actions\Stripe\UpdateSpendingLimitsOnCard;
use App\External\Stripe\SpendingControls;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\StripeCard;
use Illuminate\Support\Facades\Queue;
use Tests\AssertsActions;
use Tests\TestCase;

class UpdateAllStripeCardsFromBudgetsTest extends TestCase
{
    use AssertsActions;

    public UpdateAllStripeCardsFromBudgets $actionUnderTest;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->actionUnderTest = app(UpdateAllStripeCardsFromBudgets::class);
    }

    protected static function matchesAuth(SpendingControls $spendingControls, float $amountInPennies): bool
    {
        $stripeObject = $spendingControls->stripeObject();
        return isset($stripeObject->spending_limits) &&
            count($stripeObject->spending_limits) == 1 &&
            $stripeObject->spending_limits[0]['interval'] == 'per_authorization' &&
            $stripeObject->spending_limits[0]['amount'] == $amountInPennies;
    }

    /** @test */
    public function inactive_budget_with_customer_owner(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->cardholder()->create();
        /** @var StripeCard $stripeCard */
        $stripeCard = StripeCard::factory()->cardholder($customer)->active()->create();
        /** @var Budget $budget */
        Budget::factory()->owner($customer)->inactive()->create();

        $this->actionUnderTest->execute();

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->with(fn(...$args) => $stripeCard->is($args[0]) && self::matchesAuth($args[1], 1))
            ->once();

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->never();
    }

    /** @test */
    public function budget_with_customer_owner(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->cardholder()->create();
        /** @var StripeCard $stripeCard */
        $stripeCard = StripeCard::factory()->cardholder($customer)->active()->create();
        /** @var Budget $budget */
        $budget = Budget::factory()->owner($customer)->create();

        $this->actionUnderTest->execute();

        $neededIssuingBalance = $budget->available_to_spend;
        $neededIssuingBalancePennies = round($neededIssuingBalance * 100);

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->with(fn(...$args) => $stripeCard->is($args[0]) && self::matchesAuth($args[1], $neededIssuingBalancePennies))
            ->once();

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->with(fn(...$args) => $args[0] == $neededIssuingBalancePennies)
            ->once();
    }

    /** @test */
    public function budget_with_customer_owner_with_no_physical_card(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->cardholder()->create();
        Budget::factory()->owner($customer)->create();

        $this->actionUnderTest->execute();

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->never();

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->never();
    }

    /** @test */
    public function budget_with_customer_owner_with_inactive_card(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->cardholder()->create();
        /** @var StripeCard $stripeCard */
        $stripeCard = StripeCard::factory()->cardholder($customer)->create();
        /** @var Budget $budget */
        Budget::factory()->owner($customer)->create();

        $this->actionUnderTest->execute();

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->with(fn(...$args) => $stripeCard->is($args[0]) && self::matchesAuth($args[1], 1))
            ->once();

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->never();
    }

    /** @test */
    public function budget_with_customer_owner_with_canceled_card(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->cardholder()->create();
        /** @var StripeCard $stripeCard */
        StripeCard::factory()->cardholder($customer)->canceled()->create();
        /** @var Budget $budget */
        Budget::factory()->owner($customer)->create();

        $this->actionUnderTest->execute();

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->never();  // Don't even bother doing an update for this card

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->never();
    }

    /** @test */
    public function budget_with_customer_owner_who_is_not_member(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->cardholder()->create();
        /** @var StripeCard $stripeCard */
        $stripeCard = StripeCard::factory()->cardholder($customer)->active()->create();
        /** @var Budget $budget */
        Budget::factory()->owner($customer)->create();

        $this->actionUnderTest->execute();

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->with(fn(...$args) => $stripeCard->is($args[0]) && self::matchesAuth($args[1], 1))
            ->once();

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->never();
    }

    /** @test */
    public function budget_with_customer_owner_who_only_has_a_virtual_card(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->cardholder()->create();
        /** @var StripeCard $stripeCard */
        $stripeCard = StripeCard::factory()->cardholder($customer)->virtual()->active()->create();
        /** @var Budget $budget */
        $budget = Budget::factory()->owner($customer)->create();

        $this->actionUnderTest->execute();

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->with(fn(...$args) => $stripeCard->is($args[0]) && self::matchesAuth($args[1], 1))
            ->once();

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->never();
    }

    /** @test */
    public function budget_with_customer_owner_with_no_cardholder_id(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->create();
        /** @var Budget $budget */
        Budget::factory()->owner($customer)->create();

        $this->actionUnderTest->execute();

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->never();

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->never();
    }

    /** @test */
    public function two_budgets_with_customer_owner(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->member()->cardholder()->create();
        /** @var StripeCard $stripeCard */
        $stripeCard = StripeCard::factory()->cardholder($customer)->active()->create();
        /** @var Budget $budgetA */
        $budgetA = Budget::factory()->owner($customer)->create();
        /** @var Budget $budgetB */
        $budgetB = Budget::factory()->owner($customer)->create();


        $this->actionUnderTest->execute();

        $neededIssuingBalance = $budgetA->available_to_spend + $budgetB->available_to_spend;
        $neededIssuingBalancePennies = round($neededIssuingBalance * 100);

        $this->assertAction(UpdateSpendingLimitsOnCard::class)
            ->with(fn(...$args) => $stripeCard->is($args[0]) && self::matchesAuth($args[1], $neededIssuingBalancePennies))
            ->once();

        $this->assertAction(SetIssuingBalanceToValue::class)
            ->with(fn(...$args) => $args[0] == $neededIssuingBalancePennies)
            ->once();
    }
}
