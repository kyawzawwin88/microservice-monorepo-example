<?php

use App\Http\Controllers\EventLogController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Invoice Service API Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class);

Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);
Route::get('/invoices/correlation/{correlationId}', [InvoiceController::class, 'showByCorrelation']);

Route::get('/event-logs', [EventLogController::class, 'index']);
