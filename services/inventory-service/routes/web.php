<?php

declare(strict_types=1);

use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Metrics endpoint for Prometheus (without 'api' prefix)
Route::get('/metrics', [MetricsController::class, 'index']);

// Health check (backup route without 'api' prefix)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'inventory-service',
        'timestamp' => now()->toIso8601String(),
    ]);
});
