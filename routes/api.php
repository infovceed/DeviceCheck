<?php

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ValidateDeviceToken;
use App\Http\Controllers\Api\DeviceAuthController;
use App\Http\Controllers\Api\ReportDeviceController;
use App\Http\Controllers\Dashboard\DepartmentController;
use App\Http\Controllers\Dashboard\StatsController;
use App\Http\Controllers\Dashboard\MunicipalitiesController;

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

Route::middleware(['ws.key'])->group(function () {
    // Datos del gráfico de departamentos (serie compatible con el frontend)
    Route::get('/departments/chart', [DepartmentController::class, 'chart'])
        ->name('api.departments.chart');

    // Datos agregados de estadísticas (totales y porcentajes) para tarjetas
    Route::get('/stats', [StatsController::class, 'current'])
        ->name('api.stats.current');

    // Datos del gráfico de municipios por departamento
    Route::get('/municipalities/chart', [MunicipalitiesController::class, 'chart'])
        ->name('api.municipalities.chart');
});