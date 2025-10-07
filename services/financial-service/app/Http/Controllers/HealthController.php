<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * HealthController
 * 
 * Controller para health check do serviço.
 */
class HealthController extends Controller
{
    /**
     * Health check endpoint
     * 
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $checks = [
            'service' => 'up',
            'database' => 'unknown',
        ];

        // Verifica conexão com o banco
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'connected';
        } catch (\Exception $e) {
            $checks['database'] = 'disconnected';
            $status = 'unhealthy';
        }

        return response()->json([
            'service' => 'financial-service',
            'status' => $status,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'test' => 'FINANCIAL_SERVICE_v1.0',
        ], $status === 'healthy' ? 200 : 503);
    }
}

