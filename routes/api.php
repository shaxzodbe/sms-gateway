<?php

use Illuminate\Http\Request;
use App\Http\Controllers\EskizController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::prefix('sms')->group(function () {
    Route::post('eskiz/callback', [EskizController::class, 'index'])->name('eskiz.callback');
});
