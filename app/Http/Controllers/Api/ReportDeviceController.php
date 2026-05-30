<?php

namespace App\Http\Controllers\Api;

use App\Models\Device;
use App\Models\DeviceCheck;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportDeviceRequest;
use App\Jobs\NotifyWebSocketClients;

class ReportDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReportDeviceRequest $request)
    {
        $deviceData = $request->validated();

        try {
            $device = $request->attributes->get('resolved_device');
            if (! $device instanceof Device) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid QR code.',
                ], 422);
            }

            $deviceData['device_id'] = $device->id;
            DeviceCheck::createReport($deviceData);

            $divipole     = $device?->divipole;
            $department   = $divipole?->department?->name;
            $municipality = $divipole?->municipality?->name;
            $position     = $divipole?->position_name;

            $lines = [
                "Departamento: $department",
                "Municipio: $municipality",
                "Puesto: $position",
            ];
            try {
                $shouldNotify = cache()->add('ws.notify.pending', 1, now()->addSeconds(2));
                if ($shouldNotify) {
                    $paths = ['/ws/stats', '/ws/departments', '/ws/municipalities'];
                    NotifyWebSocketClients::dispatch($paths)->afterResponse();
                }
            } catch (\Throwable $notifyErr) {
                logger()->info('Queue dispatch for WS notify failed: ' . $notifyErr->getMessage());
            }

            return response()->json([
                'status'  => 'ok',
                'message' => implode("\n", $lines),
            ], 200);
        } catch (\Exception $e) {
            logger()->error('Error saving device report', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unknown error.',
            ], 500);
        }
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
