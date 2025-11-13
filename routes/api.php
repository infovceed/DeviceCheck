<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::get('/report-device', [App\Http\Controllers\Api\ReportDeviceController::class, 'index'])
    ->name('api.report-device');