<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class MetricsController extends Controller
{
    public function index()
    {
        $metrics = [];
        
        // Application info
        $metrics[] = '# HELP app_info Application information';
        $metrics[] = '# TYPE app_info gauge';
        $metrics[] = sprintf('app_info{service="sales-service",version="%s"} 1', config('app.version', '1.0.0'));
        
        // Up metric
        $metrics[] = '# HELP up Service is up';
        $metrics[] = '# TYPE up gauge';
        $metrics[] = 'up{service="sales-service"} 1';
        
        // HTTP requests total
        $requests = cache()->get('metrics:http_requests_total', 0);
        $metrics[] = '# HELP http_requests_total Total HTTP requests';
        $metrics[] = '# TYPE http_requests_total counter';
        $metrics[] = sprintf('http_requests_total{service="sales-service"} %d', $requests);
        
        // HTTP requests by status
        foreach (['200', '201', '400', '401', '404', '500'] as $status) {
            $count = cache()->get("metrics:http_requests_status_{$status}", 0);
            $metrics[] = sprintf('http_requests_total{service="sales-service",status="%s"} %d', $status, $count);
        }
        
        // Business metrics - Orders
        $ordersCreated = cache()->get('metrics:orders_created_total', 0);
        $metrics[] = '# HELP sales_orders_created_total Total orders created';
        $metrics[] = '# TYPE sales_orders_created_total counter';
        $metrics[] = sprintf('sales_orders_created_total{service="sales-service"} %d', $ordersCreated);
        
        $ordersConfirmed = cache()->get('metrics:orders_confirmed_total', 0);
        $metrics[] = '# HELP sales_orders_confirmed_total Total orders confirmed';
        $metrics[] = '# TYPE sales_orders_confirmed_total counter';
        $metrics[] = sprintf('sales_orders_confirmed_total{service="sales-service"} %d', $ordersConfirmed);
        
        $ordersCancelled = cache()->get('metrics:orders_cancelled_total', 0);
        $metrics[] = '# HELP sales_orders_cancelled_total Total orders cancelled';
        $metrics[] = '# TYPE sales_orders_cancelled_total counter';
        $metrics[] = sprintf('sales_orders_cancelled_total{service="sales-service"} %d', $ordersCancelled);
        
        // Business metrics - Customers
        $customersCreated = cache()->get('metrics:customers_created_total', 0);
        $metrics[] = '# HELP sales_customers_created_total Total customers created';
        $metrics[] = '# TYPE sales_customers_created_total counter';
        $metrics[] = sprintf('sales_customers_created_total{service="sales-service"} %d', $customersCreated);
        
        // Response time (simplified - using last request time)
        $responseTime = cache()->get('metrics:http_request_duration_seconds', 0.1);
        $metrics[] = '# HELP http_request_duration_seconds HTTP request duration';
        $metrics[] = '# TYPE http_request_duration_seconds gauge';
        $metrics[] = sprintf('http_request_duration_seconds{service="sales-service"} %.3f', $responseTime);
        
        // PHP/Laravel metrics
        $metrics[] = '# HELP php_memory_usage_bytes PHP memory usage';
        $metrics[] = '# TYPE php_memory_usage_bytes gauge';
        $metrics[] = sprintf('php_memory_usage_bytes{service="sales-service"} %d', memory_get_usage(true));
        
        return response(implode("\n", $metrics) . "\n", Response::HTTP_OK)
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }
}

