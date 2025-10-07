<?php

use App\Http\Controllers\AccountPayableController;
use App\Http\Controllers\AccountReceivableController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Financial Service
|--------------------------------------------------------------------------
*/

// Health Check
Route::get('/health', [HealthController::class, 'check']);

// API v1
Route::prefix('v1')->group(function () {
    
    // Suppliers (Fornecedores)
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/{supplier}', [SupplierController::class, 'show']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::put('/{supplier}', [SupplierController::class, 'update']);
    });

    // Categories (Categorias)
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);
    });

    // Accounts Payable (Contas a Pagar)
    Route::prefix('accounts-payable')->group(function () {
        Route::get('/', [AccountPayableController::class, 'index']);
        Route::post('/', [AccountPayableController::class, 'store']);
        Route::post('/{accountPayable}/pay', [AccountPayableController::class, 'pay']);
    });

    // Accounts Receivable (Contas a Receber)
    Route::prefix('accounts-receivable')->group(function () {
        Route::get('/', [AccountReceivableController::class, 'index']);
        Route::post('/', [AccountReceivableController::class, 'store']);
        Route::post('/{accountReceivable}/receive', [AccountReceivableController::class, 'receive']);
    });
});
