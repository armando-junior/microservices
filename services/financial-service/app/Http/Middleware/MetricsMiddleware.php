<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class MetricsMiddleware
{
    /**
     * Handle an incoming request and collect metrics.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Processar o request
        $response = $next($request);

        // Calcular duração
        $duration = microtime(true) - $startTime;

        // Coletar métricas apenas para rotas de API (ignorar /metrics, /health)
        $path = $request->path();
        if (!str_starts_with($path, 'metrics') && !str_starts_with($path, 'health')) {
            $this->collectMetrics($request, $response, $duration);
        }

        return $response;
    }

    /**
     * Coleta métricas HTTP e business
     */
    private function collectMetrics(Request $request, Response $response, float $duration): void
    {
        $statusCode = $response->getStatusCode();

        // Incrementar total de requests
        Cache::increment('financial_http_requests_total', 1);

        // Incrementar por status code
        Cache::increment("financial_http_requests_status_$statusCode", 1);

        // Incrementar erros (4xx e 5xx)
        if ($statusCode >= 400) {
            Cache::increment('financial_http_errors_total', 1);
        }

        // Somar duração total
        $currentDuration = Cache::get('financial_http_request_duration_seconds', 0);
        Cache::put('financial_http_request_duration_seconds', $currentDuration + $duration, now()->addDays(7));

        // Atualizar memory usage
        Cache::put('financial_php_memory_usage_bytes', memory_get_usage(true), now()->addMinutes(5));

        // Coletar métricas de negócio baseadas na rota e método
        $this->collectBusinessMetrics($request, $response);
    }

    /**
     * Coleta métricas de negócio específicas
     */
    private function collectBusinessMetrics(Request $request, Response $response): void
    {
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        // Apenas contar operações bem-sucedidas
        if ($statusCode < 200 || $statusCode >= 300) {
            return;
        }

        // ============================================
        // SUPPLIERS METRICS
        // ============================================
        if ($method === 'POST' && str_contains($path, 'api/v1/suppliers') && !str_contains($path, '/')) {
            Cache::increment('financial_suppliers_created_total', 1);
        }

        if ($method === 'PUT' && preg_match('#api/v1/suppliers/[a-f0-9-]+$#', $path)) {
            Cache::increment('financial_suppliers_updated_total', 1);
        }

        // ============================================
        // CATEGORIES METRICS
        // ============================================
        if ($method === 'POST' && str_contains($path, 'api/v1/categories') && !str_contains($path, '/')) {
            Cache::increment('financial_categories_created_total', 1);
        }

        // ============================================
        // ACCOUNTS PAYABLE METRICS
        // ============================================
        if ($method === 'POST' && str_contains($path, 'api/v1/accounts-payable') && !str_contains($path, '/pay')) {
            Cache::increment('financial_accounts_payable_created_total', 1);
            
            // Tentar capturar o valor (amount) do request
            $amount = $request->input('amount', 0);
            if ($amount > 0) {
                $currentAmount = Cache::get('financial_accounts_payable_amount_total', 0);
                Cache::put('financial_accounts_payable_amount_total', $currentAmount + $amount, now()->addDays(7));
            }
        }

        if ($method === 'POST' && str_contains($path, '/pay')) {
            Cache::increment('financial_accounts_payable_paid_total', 1);
        }

        // ============================================
        // ACCOUNTS RECEIVABLE METRICS
        // ============================================
        if ($method === 'POST' && str_contains($path, 'api/v1/accounts-receivable') && !str_contains($path, '/receive')) {
            Cache::increment('financial_accounts_receivable_created_total', 1);
            
            // Tentar capturar o valor (amount) do request
            $amount = $request->input('amount', 0);
            if ($amount > 0) {
                $currentAmount = Cache::get('financial_accounts_receivable_amount_total', 0);
                Cache::put('financial_accounts_receivable_amount_total', $currentAmount + $amount, now()->addDays(7));
            }
        }

        if ($method === 'POST' && str_contains($path, '/receive')) {
            Cache::increment('financial_accounts_receivable_received_total', 1);
        }
    }
}
