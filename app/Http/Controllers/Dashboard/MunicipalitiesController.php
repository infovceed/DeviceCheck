<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class MunicipalitiesController extends Controller
{
    public function chart(Request $request)
    {
        $departments = (array) ($request->input('department', $request->input('department', [])) ?? []);
        $departmentId = (int) (is_array($departments) && count($departments) > 0 ? $departments[0] : 0);
        $municipalities = (array) ($request->input('municipality', $request->input('municipality', [])) ?? []);
        $positions      = (array) ($request->input('position', $request->input('position', [])) ?? []);
        $date           = $request->input('chart_date', now()->toDateString());

        if ($departmentId <= 0) {
            return response()->json([
                'series' => [],
                'payload' => [],
                'timestamp' => now()->toISOString(),
                'error' => 'department id required',
            ], 200);
        }

        [$labels, $mtotal, $mcheckin, $mcheckout] = Department::getMunicipalityChartData(
            $date,
            (int)$departmentId,
            $municipalities,
            $positions
        );

        $series = [
            ['labels' => $labels, 'name' => __('Meta'), 'values' => $mtotal],
            ['labels' => $labels, 'name' => __('Arrival'), 'values' => $mcheckin],
            ['labels' => $labels, 'name' => __('Check-out'), 'values' => $mcheckout],
        ];

        return response()->json([
            'series' => $series,
            'payload' => $series,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
