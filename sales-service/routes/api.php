<?php

use App\Http\Controllers\EventLogController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sales Service API Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class);

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::post('/orders/{id}/retry', [OrderController::class, 'retry']);
Route::patch('/orders/{id}/deliver', [OrderController::class, 'deliver']);
Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
Route::get('/orders/correlation/{correlationId}', [OrderController::class, 'showByCorrelation']);

Route::get('/event-logs', [EventLogController::class, 'index']);
