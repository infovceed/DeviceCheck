<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ValidateDeviceToken;
use App\Http\Controllers\Api\DeviceAuthController;
use App\Http\Controllers\Api\ReportDeviceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí puedes registrar las rutas para tu API. Todas las rutas que se 
| definan en este archivo se cargan con el prefijo "/api".
|
*/

Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Ruta de prueba funcionando 🚀'
    ]);
});


// Emitir token para dispositivos (requiere X-API-KEY)
Route::get('/auth/device-token', [DeviceAuthController::class, 'issueToken'])
    ->name('api.device.issue-token');

// Proteger el reporte de dispositivos con validación de token propio
Route::middleware([ValidateDeviceToken::class])
    ->get('/report-device', [ReportDeviceController::class, 'index'])
    ->name('api.report-device');