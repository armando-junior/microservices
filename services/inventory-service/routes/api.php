<?php

declare(strict_types=1);

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
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

// Public routes (TODO: adicionar autenticação JWT depois)
Route::prefix('v1')->group(function () {
    
    // Product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::patch('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::patch('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    // Stock routes
    Route::prefix('stock')->group(function () {
        Route::get('/product/{productId}', [StockController::class, 'show']);
        Route::post('/product/{productId}/increase', [StockController::class, 'increase']);
        Route::post('/product/{productId}/decrease', [StockController::class, 'decrease']);
        Route::get('/low-stock', [StockController::class, 'lowStock']);
        Route::get('/depleted', [StockController::class, 'depleted']);
    });
});
