<?php

declare(strict_types=1);

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Inventory Service
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'inventory-service',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Metrics endpoint for Prometheus
Route::get('/metrics', [MetricsController::class, 'index']);

// API v1 routes
Route::prefix('v1')->group(function () {
    
    // Public routes (read-only, no authentication required)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
    });

    Route::prefix('stock')->group(function () {
        Route::get('/product/{productId}', [StockController::class, 'show']);
        Route::get('/low-stock', [StockController::class, 'lowStock']);
        Route::get('/depleted', [StockController::class, 'depleted']);
    });

    // Protected routes (require JWT authentication)
    Route::middleware('jwt.auth')->group(function () {
        
        // Product management (write operations)
        Route::prefix('products')->group(function () {
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/{id}', [ProductController::class, 'update']);
            Route::patch('/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);
        });

        // Category management (write operations)
        Route::prefix('categories')->group(function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::patch('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });

        // Stock management (write operations)
        Route::prefix('stock')->group(function () {
            Route::post('/product/{productId}/increase', [StockController::class, 'increase']);
            Route::post('/product/{productId}/decrease', [StockController::class, 'decrease']);
        });
    });
});
