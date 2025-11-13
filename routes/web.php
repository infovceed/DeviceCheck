<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateStorage;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/storage/{any}',function ($any) {
    return $any;
})->where('any', '.*\.(jpg|jpeg|png|gif|bmp|pdf|xls|xlsx|doc|docx|txt)$')->middleware(AuthenticateStorage::class);
