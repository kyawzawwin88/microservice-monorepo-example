<?php

use App\Http\Controllers\EventLogController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Service API Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class);

Route::get('/inventory', [InventoryItemController::class, 'index']);
Route::get('/inventory/all', [InventoryItemController::class, 'all']);
Route::post('/inventory', [InventoryItemController::class, 'store']);
Route::get('/inventory/{id}', [InventoryItemController::class, 'show']);
Route::put('/inventory/{id}', [InventoryItemController::class, 'update']);
Route::delete('/inventory/{id}', [InventoryItemController::class, 'destroy']);

Route::get('/reservations', [InventoryReservationController::class, 'index']);
Route::get('/reservations/correlation/{correlationId}', [InventoryReservationController::class, 'showByCorrelation']);

Route::get('/event-logs', [EventLogController::class, 'index']);
