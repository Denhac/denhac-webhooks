<?php

namespace App\Console\Commands;

use App\External\QuickBooks\QuickBookReferences;
use Illuminate\Console\Command;
use QuickBooksOnline\API\Data\IPPClass;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\ReportService\ReportName;
use QuickBooksOnline\API\ReportService\ReportService;

class PrintQuickBooksBudgets extends Command
{
    protected $signature = 'denhac:print-quick-books-budgets';

    protected $description = 'This is just a test command to print QuickBooks budgets';

    public function handle()
    {
        /** @var DataService $dataService */
        $dataService = app(DataService::class);
        $serviceContext = $dataService->getServiceContext();
        $references = app(QuickBookReferences::class);
        $classes = collect($dataService->FindAll('Class'));

        $activeBudgets = $classes->filter(fn ($class) => $class->ParentRef == $references->budgetClassActive->value);

        foreach ($activeBudgets as $budget) {
            /** @var IPPClass $budget */
            $reportService = new ReportService($serviceContext);
            $reportService->setStartDate('2019-01-01');
            $reportService->setEndDate('2024-01-25');
            $reportService->setClassid($budget->Id);
            $reportService->setAccountingMethod('Accrual');
            /** @var \stdClass $profitAndLossReport */
            $profitAndLossReport = $reportService->executeReport(ReportName::PROFITANDLOSS);
            $columns = collect($profitAndLossReport->Columns->Column)->map(function ($column) {
                return $column->MetaData[0]->Value;
            })->values();
            $netRevenueSection = collect($profitAndLossReport->Rows->Row)->first(function ($row) {
                return $row->group == 'NetIncome';
            });
            $netRevenueData = collect($netRevenueSection->Summary->ColData)->map(function ($colData) {
                return $colData->value;
            });

            $totalColumn = $columns->search('total');
            $value = -floatval($netRevenueData[$totalColumn]);
            $this->info("{$budget->Name}\tSpent: $value");
        }
    }
}
