<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateStorage;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Platform\PlatformLoginController;
use Orchid\Platform\Dashboard;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? config('platform.index') : 'platform.login');
});
Route::get('/storage/{any}',function ($any) {
    return $any;
})->where('any', '.*\.(jpg|jpeg|png|gif|bmp|pdf|xls|xlsx|doc|docx|txt)$')->middleware(AuthenticateStorage::class);

// Descargar export generado (requiere sesión y enlace firmado)
Route::get('/exports/download', [ExportController::class, 'download'])
    ->name('exports.download')
    ->middleware(['auth', 'signed']);

// Orchid login override (usuario/documento en vez de email)
Route::prefix(Dashboard::prefix('/'))
    ->as('platform.')
    ->middleware(config('platform.middleware.public'))
    ->group(function () {
        Route::get('login', [PlatformLoginController::class, 'showLoginForm'])
            ->name('login');

        Route::middleware('throttle:60,1')
            ->post('login', [PlatformLoginController::class, 'login'])
            ->name('login.auth');

        Route::get('lock', [PlatformLoginController::class, 'resetCookieLockMe'])
            ->name('login.lock');
    });
