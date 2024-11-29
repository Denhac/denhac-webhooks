<?php

namespace App\Actions\QuickBooks;

use Carbon\Carbon;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\ReportService\ReportName;
use QuickBooksOnline\API\ReportService\ReportService;

/**
 * Runs a report through QuickBooks to determine spend per QuickBooks class.
 */
class GetAmountSpentByClass
{
    public function execute($classId, Carbon $startDate, Carbon $endDate): float
    {
        /** @var DataService $dataService */
        $dataService = app(DataService::class);

        $serviceContext = $dataService->getServiceContext();

        $reportService = new ReportService($serviceContext);
        $reportService->setStartDate($startDate->format("Y-m-d"));
        $reportService->setEndDate($endDate->format("Y-m-d"));
        $reportService->setClassid($classId);
        $reportService->setAccountingMethod("Accrual");

        /** @var \stdClass $profitAndLossReport */
        $profitAndLossReport = $reportService->executeReport(ReportName::PROFITANDLOSS);

        // QuickBooks reports can be very nested which makes the format a little hard to parse. One consistency is the
        // columns are all the same for every level of the nesting so we start by grabbing the column index for the
        // "total"
        $columns = collect($profitAndLossReport->Columns->Column)->map(function ($column) {
            return $column->MetaData[0]->Value;
        })->values();
        $totalColumn = $columns->search("total");

        // We then look for the "NetIncome" group (also called "Net Revenue in the row itself") and extract the net
        // revenue value.
        $netRevenueSection = collect($profitAndLossReport->Rows->Row)->first(function ($row) {
            return $row->group == "NetIncome";
        });
        $netRevenueData = collect($netRevenueSection->Summary->ColData)->map(function ($colData) {
            return $colData->value;
        });
        $netRevenue = floatval($netRevenueData[$totalColumn]);

        if(abs($netRevenue) < 0.01) {  // Handles 0 and anything less than a penny though I didn't see that in practice
            return 0;
        }

        // Net income is usually negative for budgets since it's all outgoing and no incoming. We want to return spend
        // so we negate that value.
        return -$netRevenue;
    }
}
