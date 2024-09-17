<?php

namespace Tests\Unit\Actions\QuickBooks;

use App\Actions\QuickBooks\GetAmountSpentByClass;
use App\Actions\QuickBooks\PullCurrentlyUsedAmountForBudgetFromQuickBooks;
use App\Models\Budget;
use App\Models\Customer;
use Carbon\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class PullCurrentlyUsedAmountForBudgetFromQuickBooksTest extends TestCase
{
    private const ONE_TIME_CLASS_ID = 1234;
    private const POOL_CLASS_ID = 5678;
    private const MONTHLY_CLASS_ID = 9123;
    private const YEARLY_CLASS_ID = 4802;

    private PullCurrentlyUsedAmountForBudgetFromQuickBooks $pullCurrentlyUsedAmountForBudgetFromQuickBooks;
    private GetAmountSpentByClass|MockInterface $getAmountSpentByClass;

    private Customer $customer;

    private Carbon $startOfBudgetTracking;
    private Carbon $endOfToday;
    private Carbon $startOfMonth;
    private Carbon $endOfMonth;
    private Carbon $startOfYear;
    private Carbon $endOfYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getAmountSpentByClass = $this->mock(GetAmountSpentByClass::class);

        $this->pullCurrentlyUsedAmountForBudgetFromQuickBooks = app(PullCurrentlyUsedAmountForBudgetFromQuickBooks::class);

        $this->customer = Customer::create([
            'id' => $this->faker->randomNumber(),
            'username' => $this->faker->userName(),
            'member' => true,
            'email' => $this->faker->email(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ]);

        $today = Carbon::today('PST');
        $this->startOfBudgetTracking = Carbon::create(2019, tz: 'PST');
        $this->endOfToday = $today->copy()->endOfDay();
        $this->startOfMonth = $today->copy()->startOfMonth();
        $this->endOfMonth = $today->copy()->endOfMonth();
        $this->startOfYear = $today->copy()->startOfYear();
        $this->endOfYear = $today->copy()->endOfYear();

        Budget::setExtraBufferAmount(0);  // No buffer for these tests
    }

    /** @test */
    public function one_time_budget_is_measured_against_the_start_of_our_budget_tracking(): void
    {
        /** @var Budget $budget */
        $budget = Budget::create([
            'quickbooks_class_id' => self::ONE_TIME_CLASS_ID,
            'name' => 'One time Budget',
            'type' => Budget::TYPE_ONE_TIME,
            'active' => true,
            'owner_type' => Customer::getActualClassNameForMorph(Customer::class),
            'owner_id' => $this->customer->id,
            'allocated_amount' => 1000,
            'currently_used' => 20,
        ]);

        $this->assertEquals(980, $budget->available_to_spend);  // Sanity check

        $this->getAmountSpentByClass
            ->expects('execute')
            ->withArgs(function(...$args) {
                return $args[0] == self::ONE_TIME_CLASS_ID &&
                    $args[1] == $this->startOfBudgetTracking &&
                    $args[2] == $this->endOfToday;
            })
            ->andReturn(600);

        $this->pullCurrentlyUsedAmountForBudgetFromQuickBooks->execute($budget);

        $budget->refresh();  // While the action probably modified this object, refreshing ensures it saved it to the DB

        $this->assertEquals(1000, $budget->allocated_amount);
        $this->assertEquals(600, $budget->currently_used);
        $this->assertEquals(400, $budget->available_to_spend);
    }

    /** @test */
    public function pool_budget_is_measured_against_the_start_of_our_budget_tracking(): void
    {
        /** @var Budget $budget */
        $budget = Budget::create([
            'quickbooks_class_id' => self::POOL_CLASS_ID,
            'name' => 'Pool Budget',
            'type' => Budget::TYPE_POOL,
            'active' => true,
            'owner_type' => Customer::getActualClassNameForMorph(Customer::class),
            'owner_id' => $this->customer->id,
            'allocated_amount' => 1000,
            'currently_used' => 300,
        ]);

        $this->assertEquals(700, $budget->available_to_spend);  // Sanity check

        $this->getAmountSpentByClass
            ->expects('execute')
            ->withArgs(function(...$args) {
                return $args[0] == self::POOL_CLASS_ID &&
                    $args[1] == $this->startOfBudgetTracking &&
                    $args[2] == $this->endOfToday;
            })
            ->andReturn(-400);  // We have made $400 compared to our break even point

        $this->pullCurrentlyUsedAmountForBudgetFromQuickBooks->execute($budget);

        $budget->refresh();  // While the action probably modified this object, refreshing ensures it saved it to the DB

        $this->assertEquals(1000, $budget->allocated_amount);
        $this->assertEquals(600, $budget->currently_used);
        $this->assertEquals(400, $budget->available_to_spend);
    }

    /** @test */
    public function pool_budget_is_zero_if_our_spend_is_positive(): void
    {
        /** @var Budget $budget */
        $budget = Budget::create([
            'quickbooks_class_id' => self::POOL_CLASS_ID,
            'name' => 'Pool Budget',
            'type' => Budget::TYPE_POOL,
            'active' => true,
            'owner_type' => Customer::getActualClassNameForMorph(Customer::class),
            'owner_id' => $this->customer->id,
            'allocated_amount' => 1000,
            'currently_used' => 300,
        ]);

        $this->assertEquals(700, $budget->available_to_spend);  // Sanity check

        $this->getAmountSpentByClass
            ->expects('execute')
            ->withArgs(function(...$args) {
                return $args[0] == self::POOL_CLASS_ID &&
                    $args[1] == $this->startOfBudgetTracking &&
                    $args[2] == $this->endOfToday;
            })
            ->andReturn(30);  // We have spent $30 that we don't have compared to our pool

        $this->pullCurrentlyUsedAmountForBudgetFromQuickBooks->execute($budget);

        $budget->refresh();  // While the action probably modified this object, refreshing ensures it saved it to the DB

        $this->assertEquals(1000, $budget->allocated_amount);
        $this->assertEquals(1030, $budget->currently_used);
        $this->assertEquals(0, $budget->available_to_spend);
    }

    /** @test */
    public function pool_budget_is_maxed_out_at_allocated(): void
    {
        /** @var Budget $budget */
        $budget = Budget::create([
            'quickbooks_class_id' => self::POOL_CLASS_ID,
            'name' => 'Pool Budget',
            'type' => Budget::TYPE_POOL,
            'active' => true,
            'owner_type' => Customer::getActualClassNameForMorph(Customer::class),
            'owner_id' => $this->customer->id,
            'allocated_amount' => 1000,
            'currently_used' => 300,
        ]);

        $this->assertEquals(700, $budget->available_to_spend);  // Sanity check

        $this->getAmountSpentByClass
            ->expects('execute')
            ->withArgs(function(...$args) {
                return $args[0] == self::POOL_CLASS_ID &&
                    $args[1] == $this->startOfBudgetTracking &&
                    $args[2] == $this->endOfToday;
            })
            ->andReturn(-1030);  // We have made $1030 compared to our break even point

        $this->pullCurrentlyUsedAmountForBudgetFromQuickBooks->execute($budget);

        $budget->refresh();  // While the action probably modified this object, refreshing ensures it saved it to the DB

        $this->assertEquals(1000, $budget->allocated_amount);
        $this->assertEquals(0, $budget->currently_used);
        $this->assertEquals(1000, $budget->available_to_spend);
    }

    /** @test */
    public function monthly_budget_is_tracked_against_start_and_end_of_month(): void
    {
        /** @var Budget $budget */
        $budget = Budget::create([
            'quickbooks_class_id' => self::MONTHLY_CLASS_ID,
            'name' => 'Pool Budget',
            'type' => Budget::TYPE_RECURRING_MONTHLY,
            'active' => true,
            'owner_type' => Customer::getActualClassNameForMorph(Customer::class),
            'owner_id' => $this->customer->id,
            'allocated_amount' => 300,
            'currently_used' => 150,
        ]);

        $this->assertEquals(150, $budget->available_to_spend);  // Sanity check

        $this->getAmountSpentByClass
            ->expects('execute')
            ->withArgs(function(...$args) {
                return $args[0] == self::MONTHLY_CLASS_ID &&
                    $args[1] == $this->startOfMonth &&
                    $args[2] == $this->endOfMonth;
            })
            ->andReturn(200);

        $this->pullCurrentlyUsedAmountForBudgetFromQuickBooks->execute($budget);

        $budget->refresh();  // While the action probably modified this object, refreshing ensures it saved it to the DB

        $this->assertEquals(300, $budget->allocated_amount);
        $this->assertEquals(200, $budget->currently_used);
        $this->assertEquals(100, $budget->available_to_spend);
    }

    /** @test */
    public function monthly_yearly_is_tracked_against_start_and_end_of_year(): void
    {
        /** @var Budget $budget */
        $budget = Budget::create([
            'quickbooks_class_id' => self::YEARLY_CLASS_ID,
            'name' => 'Pool Budget',
            'type' => Budget::TYPE_RECURRING_YEARLY,
            'active' => true,
            'owner_type' => Customer::getActualClassNameForMorph(Customer::class),
            'owner_id' => $this->customer->id,
            'allocated_amount' => 500,
            'currently_used' => 150,
        ]);

        $this->assertEquals(350, $budget->available_to_spend);  // Sanity check

        $this->getAmountSpentByClass
            ->expects('execute')
            ->withArgs(function(...$args) {
                return $args[0] == self::YEARLY_CLASS_ID &&
                    $args[1] == $this->startOfYear &&
                    $args[2] == $this->endOfYear;
            })
            ->andReturn(300);

        $this->pullCurrentlyUsedAmountForBudgetFromQuickBooks->execute($budget);

        $budget->refresh();  // While the action probably modified this object, refreshing ensures it saved it to the DB

        $this->assertEquals(500, $budget->allocated_amount);
        $this->assertEquals(300, $budget->currently_used);
        $this->assertEquals(200, $budget->available_to_spend);
    }
}
