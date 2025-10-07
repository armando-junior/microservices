<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class MetricsController extends Controller
{
    /**
     * Expõe métricas no formato Prometheus
     */
    public function index(): Response
    {
        $metrics = [];

        // ==================================================
        // RED METRICS (Request, Error, Duration)
        // ==================================================

        // Total de requests HTTP
        $totalRequests = Cache::get('financial_http_requests_total', 0);
        $metrics[] = "# HELP financial_http_requests_total Total HTTP requests";
        $metrics[] = "# TYPE financial_http_requests_total counter";
        $metrics[] = "financial_http_requests_total $totalRequests";

        // Requests por status code
        foreach ([200, 201, 400, 404, 422, 500, 503] as $code) {
            $count = Cache::get("financial_http_requests_status_$code", 0);
            $metrics[] = "financial_http_requests_by_status{status=\"$code\"} $count";
        }

        // Total de erros (4xx + 5xx)
        $totalErrors = Cache::get('financial_http_errors_total', 0);
        $metrics[] = "\n# HELP financial_http_errors_total Total HTTP errors (4xx + 5xx)";
        $metrics[] = "# TYPE financial_http_errors_total counter";
        $metrics[] = "financial_http_errors_total $totalErrors";

        // Duração das requisições (soma total em segundos)
        $requestDuration = Cache::get('financial_http_request_duration_seconds', 0);
        $metrics[] = "\n# HELP financial_http_request_duration_seconds Total request duration";
        $metrics[] = "# TYPE financial_http_request_duration_seconds counter";
        $metrics[] = "financial_http_request_duration_seconds $requestDuration";

        // ==================================================
        // BUSINESS METRICS - SUPPLIERS
        // ==================================================

        $suppliersCreated = Cache::get('financial_suppliers_created_total', 0);
        $metrics[] = "\n# HELP financial_suppliers_created_total Total suppliers created";
        $metrics[] = "# TYPE financial_suppliers_created_total counter";
        $metrics[] = "financial_suppliers_created_total $suppliersCreated";

        $suppliersUpdated = Cache::get('financial_suppliers_updated_total', 0);
        $metrics[] = "\n# HELP financial_suppliers_updated_total Total suppliers updated";
        $metrics[] = "# TYPE financial_suppliers_updated_total counter";
        $metrics[] = "financial_suppliers_updated_total $suppliersUpdated";

        // ==================================================
        // BUSINESS METRICS - CATEGORIES
        // ==================================================

        $categoriesCreated = Cache::get('financial_categories_created_total', 0);
        $metrics[] = "\n# HELP financial_categories_created_total Total categories created";
        $metrics[] = "# TYPE financial_categories_created_total counter";
        $metrics[] = "financial_categories_created_total $categoriesCreated";

        // ==================================================
        // BUSINESS METRICS - ACCOUNTS PAYABLE
        // ==================================================

        $accountsPayableCreated = Cache::get('financial_accounts_payable_created_total', 0);
        $metrics[] = "\n# HELP financial_accounts_payable_created_total Total accounts payable created";
        $metrics[] = "# TYPE financial_accounts_payable_created_total counter";
        $metrics[] = "financial_accounts_payable_created_total $accountsPayableCreated";

        $accountsPayablePaid = Cache::get('financial_accounts_payable_paid_total', 0);
        $metrics[] = "\n# HELP financial_accounts_payable_paid_total Total accounts payable paid";
        $metrics[] = "# TYPE financial_accounts_payable_paid_total counter";
        $metrics[] = "financial_accounts_payable_paid_total $accountsPayablePaid";

        $accountsPayableAmount = Cache::get('financial_accounts_payable_amount_total', 0);
        $metrics[] = "\n# HELP financial_accounts_payable_amount_total Total amount of accounts payable";
        $metrics[] = "# TYPE financial_accounts_payable_amount_total counter";
        $metrics[] = "financial_accounts_payable_amount_total $accountsPayableAmount";

        // ==================================================
        // BUSINESS METRICS - ACCOUNTS RECEIVABLE
        // ==================================================

        $accountsReceivableCreated = Cache::get('financial_accounts_receivable_created_total', 0);
        $metrics[] = "\n# HELP financial_accounts_receivable_created_total Total accounts receivable created";
        $metrics[] = "# TYPE financial_accounts_receivable_created_total counter";
        $metrics[] = "financial_accounts_receivable_created_total $accountsReceivableCreated";

        $accountsReceivableReceived = Cache::get('financial_accounts_receivable_received_total', 0);
        $metrics[] = "\n# HELP financial_accounts_receivable_received_total Total accounts receivable received";
        $metrics[] = "# TYPE financial_accounts_receivable_received_total counter";
        $metrics[] = "financial_accounts_receivable_received_total $accountsReceivableReceived";

        $accountsReceivableAmount = Cache::get('financial_accounts_receivable_amount_total', 0);
        $metrics[] = "\n# HELP financial_accounts_receivable_amount_total Total amount of accounts receivable";
        $metrics[] = "# TYPE financial_accounts_receivable_amount_total counter";
        $metrics[] = "financial_accounts_receivable_amount_total $accountsReceivableAmount";

        // ==================================================
        // SYSTEM METRICS
        // ==================================================

        $memoryUsage = memory_get_usage(true);
        $metrics[] = "\n# HELP financial_php_memory_usage_bytes PHP memory usage in bytes";
        $metrics[] = "# TYPE financial_php_memory_usage_bytes gauge";
        $metrics[] = "financial_php_memory_usage_bytes $memoryUsage";

        // Timestamp da coleta
        $timestamp = now()->timestamp * 1000; // Milissegundos
        $metrics[] = "\n# HELP financial_scrape_timestamp_ms Timestamp of metrics scrape";
        $metrics[] = "# TYPE financial_scrape_timestamp_ms gauge";
        $metrics[] = "financial_scrape_timestamp_ms $timestamp";

        $content = implode("\n", $metrics);

        return response($content, 200)
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }
}

