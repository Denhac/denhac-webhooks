<?php

namespace App\Console\Commands;

use App\External\QuickBooks\QuickBookReferences;
use Illuminate\Console\Command;
use QuickBooksOnline\API\Core\ServiceContext;
use QuickBooksOnline\API\Data\IPPClass;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\ReportService\ReportName;
use QuickBooksOnline\API\ReportService\ReportService;

class PrintQuickBooksBudgets extends Command
{
    protected $signature = 'denhac:print-quick-books-budgets';

    protected $description = 'This is just a test command to print QuickBooks budgets';

    protected DataService $dataService;
    protected ServiceContext $serviceContext;
    private QuickBookReferences $references;

    public function __construct(
        DataService         $dataService,
        QuickBookReferences $references
    )
    {
        $this->dataService = $dataService;
        $this->serviceContext = $dataService->getServiceContext();
        $this->references = $references;
    }

    public function handle()
    {
        $classes = collect($this->dataService->FindAll('Class'));

        $activeBudgets = $classes->filter(fn($class) => $class->ParentRef == $this->references->budgetClassActive);

        foreach ($activeBudgets as $budget) {
            /** @var IPPClass $budget */
            $reportService = new ReportService($this->serviceContext);
            $reportService->setStartDate("2019-01-01");
            $reportService->setEndDate("2024-01-25");
            $reportService->setClassid($budget->Id);
            $reportService->setAccountingMethod("Accrual");
            /** @var \stdClass $profitAndLossReport */
            $profitAndLossReport = $reportService->executeReport(ReportName::PROFITANDLOSS);
            $columns = collect($profitAndLossReport->Columns->Column)->map(function ($column) {
                return $column->MetaData[0]->Value;
            })->values();
            $netRevenueSection = collect($profitAndLossReport->Rows->Row)->first(function ($row) {
                return $row->group == "NetIncome";
            });
            $netRevenueData = collect($netRevenueSection->Summary->ColData)->map(function ($colData) {
                return $colData->value;
            });

            $totalColumn = $columns->search("total");
            $value = floatval($netRevenueData[$totalColumn]);
            $this->info("{$budget->Name} $value");
        }
    }
}
