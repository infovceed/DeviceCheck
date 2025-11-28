<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateStorage;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/storage/{any}',function ($any) {
    return $any;
})->where('any', '.*\.(jpg|jpeg|png|gif|bmp|pdf|xls|xlsx|doc|docx|txt)$')->middleware(AuthenticateStorage::class);

// Descargar export generado (requiere sesión y enlace firmado)
Route::get('/exports/download', [ExportController::class, 'download'])
    ->name('exports.download')
    ->middleware(['auth', 'signed']);
