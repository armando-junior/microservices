<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Health check (root level)
Route::get('/health', [HealthController::class, 'check']);

// Metrics endpoint for Prometheus
Route::get('/metrics', [MetricsController::class, 'index']);
