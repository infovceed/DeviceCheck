<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function chart(Request $request)
    {
        // Aplicar filtros directamente desde la query, sin depender de auth
        // Soportar tanto 'key' como 'key[]'
        $departmentID = (array) ($request->input('department', $request->input('department', [])) ?? []);
        if (empty($departmentID)) {
            $departmentID = (array) ($request->input('department', []) ?? []);
        }
        $municipalities = (array) ($request->input('municipality', $request->input('municipality', [])) ?? []);
        $positions = (array) ($request->input('position', $request->input('position', [])) ?? []);
        $date = $request->input('chart_date', now()->toDateString());

        [$labels, $valuesTotal, $valuesReported, $reportedCheckout] = Department::getChartData(
            $date,
            $departmentID,
            $municipalities,
            $positions
        );

        $series = [
            ['labels' => $labels, 'name' => __('Meta'), 'values' => $valuesTotal],
            ['labels' => $labels, 'name' => __('Arrival'), 'values' => $valuesReported],
            ['labels' => $labels, 'name' => __('Check-out'), 'values' => $reportedCheckout],
        ];

        return response()->json([
            'series' => $series,
            'payload' => $series,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
