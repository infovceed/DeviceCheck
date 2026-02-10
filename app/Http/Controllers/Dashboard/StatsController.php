<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function current(Request $request)
    {
        $departmentID  = (array) ($request->input('department', $request->input('department', [])) ?? []);
        $municipalities = (array) ($request->input('municipality', $request->input('municipality', [])) ?? []);
        $positions      = (array) ($request->input('position', $request->input('position', [])) ?? []);
        $date           = $request->input('chart_date', now()->toDateString());

        [$labels, $valuesTotal, $valuesReported, $reportedCheckout] = Department::getChartData(
            $date,
            $departmentID,
            $municipalities,
            $positions
        );

        $totalReportedIn  = array_sum($valuesReported);
        $totalReportedOut = array_sum($reportedCheckout);
        $totalDevicesSum  = array_sum($valuesTotal);
        $percentageIn     = $totalDevicesSum ? round(($totalReportedIn / $totalDevicesSum) * 100, 1) : 0;
        $percentageOut    = $totalDevicesSum ? round(($totalReportedOut / $totalDevicesSum) * 100, 1) : 0;

        $stats = [
            'totalRecords'     => $totalDevicesSum,
            'totalReported'    => $totalReportedIn + $totalReportedOut,
            'totalReportedIn'  => $totalReportedIn,
            'totalReportedOut' => $totalReportedOut,
            'percentage'       => $totalDevicesSum ? round((($totalReportedIn + $totalReportedOut) / $totalDevicesSum) * 100, 2) : 0,
            'percentageIn'     => $percentageIn,
            'percentageOut'    => $percentageOut,
        ];

        return response()->json([
            'stats' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
