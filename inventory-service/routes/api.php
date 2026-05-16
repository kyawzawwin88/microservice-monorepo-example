<?php

use App\Http\Controllers\EventLogController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryReservationController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\StorageLocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Service API Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class);

Route::get('/inventory/locations', [StorageLocationController::class, 'index']);
Route::post('/inventory/locations', [StorageLocationController::class, 'store']);
Route::put('/inventory/locations/{id}', [StorageLocationController::class, 'update'])->whereNumber('id');
Route::post('/inventory/locations/{id}/deactivate', [StorageLocationController::class, 'deactivate'])->whereNumber('id');

Route::post('/inventory/stock-in', [StockMovementController::class, 'stockIn']);
Route::post('/inventory/stock-out', [StockMovementController::class, 'stockOut']);
Route::post('/inventory/transfers', [StockMovementController::class, 'transfer']);

Route::get('/inventory', [InventoryItemController::class, 'index']);
Route::get('/inventory/all', [InventoryItemController::class, 'all']);
Route::post('/inventory', [InventoryItemController::class, 'store']);
Route::get('/inventory/{id}/stock-report', [StockReportController::class, 'show'])->whereNumber('id');
Route::post('/inventory/{id}/dimensions/{dimensionId}/values', [InventoryItemController::class, 'addDimensionValue'])
    ->whereNumber(['id', 'dimensionId']);
Route::get('/inventory/{id}', [InventoryItemController::class, 'show'])->whereNumber('id');
Route::put('/inventory/{id}', [InventoryItemController::class, 'update'])->whereNumber('id');
Route::delete('/inventory/{id}', [InventoryItemController::class, 'destroy'])->whereNumber('id');

Route::get('/reservations', [InventoryReservationController::class, 'index']);
Route::get('/reservations/correlation/{correlationId}', [InventoryReservationController::class, 'showByCorrelation']);

Route::get('/event-logs', [EventLogController::class, 'index']);
