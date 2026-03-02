<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Carbon;

class DeviceDailyReportService
{
    public function buildRows(Carbon $startDate, Carbon $endDate): array
    {
        $rows = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lessThanOrEqualTo($endDate)) {
            [, $valuesTotal, $valuesReported, $reportedCheckout] = Department::getChartData($currentDate->toDateString());

            $totalDevices = array_sum($valuesTotal);
            $totalReportedIn = array_sum($valuesReported);
            $totalReportedOut = array_sum($reportedCheckout);
            $percentageIn = $totalDevices > 0 ? round(($totalReportedIn / $totalDevices) * 100, 1) : 0;
            $percentageOut = $totalDevices > 0 ? round(($totalReportedOut / $totalDevices) * 100, 1) : 0;

            $rows[] = [
                $currentDate->format('d-m-Y'),
                $totalDevices,
                $percentageIn,
                $percentageOut,
                $totalReportedIn,
                $totalReportedOut,
            ];

            $currentDate->addDay();
        }

        return $rows;
    }
}
