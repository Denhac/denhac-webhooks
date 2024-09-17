<?php

namespace App\Console\Commands;

use App\Actions\QuickBooks\GetAmountSpentByClass;
use App\External\QuickBooks\QuickBookReferences;
use Carbon\Carbon;
use Illuminate\Console\Command;
use QuickBooksOnline\API\DataService\DataService;

class PrintQuickBooksBudgets extends Command
{
    protected $signature = 'denhac:print-quick-books-budgets';

    protected $description = 'This is just a test command to print QuickBooks budgets';
    private GetAmountSpentByClass $getAmountSpentByClass;

    public function __construct(
        GetAmountSpentByClass $getAmountSpentByClass
    )
    {
        parent::__construct();

        $this->getAmountSpentByClass = $getAmountSpentByClass;
    }

    public function handle()
    {
        /** @var DataService $dataService */
        $dataService = app(DataService::class);
        $references = app(QuickBookReferences::class);
        $classes = collect($dataService->FindAll('Class'));

        $activeBudgets = $classes->filter(fn($class) => $class->ParentRef == $references->budgetClassActive->value);

        foreach ($activeBudgets as $budget) {
            $spent = $this->getAmountSpentByClass->execute(
                $budget->id,
                Carbon::createFromDate(2019, 1, 1),
                Carbon::now()
            );

            $this->info("{$budget->Name}\tSpent: $spent");
        }
    }
}
