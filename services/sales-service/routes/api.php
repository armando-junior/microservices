<?php

declare(strict_types=1);

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sales Service
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'sales-service',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// API v1 routes
Route::prefix('v1')->group(function () {
    
    // Protected routes (JWT authentication required)
    Route::middleware('jwt.auth')->group(function () {
        
        // Customer routes
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::get('/{id}', [CustomerController::class, 'show']);
        });

        // Order routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{id}', [OrderController::class, 'show']);
            
            // Order actions
            Route::post('/{id}/items', [OrderController::class, 'addItem']);
            Route::post('/{id}/confirm', [OrderController::class, 'confirm']);
            Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
        });
    });
});