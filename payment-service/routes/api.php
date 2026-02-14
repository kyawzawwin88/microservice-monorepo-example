<?php

use App\Http\Controllers\EventLogController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Service API Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class);

Route::get('/payments', [PaymentController::class, 'index']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);
Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
Route::get('/payments/correlation/{correlationId}', [PaymentController::class, 'showByCorrelation']);

Route::get('/event-logs', [EventLogController::class, 'index']);
