<?php

namespace App\Http\Controllers\Api;

use App\Models\Device;
use App\Models\DeviceCheck;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportDeviceRequest;

class ReportDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ReportDeviceRequest $request)
    {

        $deviceData = $request->validated();
        try {
            $deviceID = DeviceCheck::saveReport($deviceData);
            $device   = Device::find($deviceID)->first();
            $divipole = $device?->divipole()->first();
            $department   = $divipole?->department()->first()?->name;
            $municipality = $divipole?->municipality()->first()?->name;
            $position     = $divipole?->position_name;

            $lines = [
                "Departamento: $department",
                "Municipio: $municipality",
                "Puesto: $position",
            ];

            return response()->json([
                'status'  => 'ok',
                'message' => implode("\n", $lines),
            ], 200);
        } catch (\Exception $e) {
            logger()->error('Error saving device report: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => __('An error occurred while processing the report. Please try again.'),
            ], 500);
        }
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
