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
        $metrics[] = sprintf('app_info{service="inventory-service",version="%s"} 1', config('app.version', '1.0.0'));
        
        // Up metric
        $metrics[] = '# HELP up Service is up';
        $metrics[] = '# TYPE up gauge';
        $metrics[] = 'up{service="inventory-service"} 1';
        
        // HTTP requests total
        $requests = cache()->get('metrics:http_requests_total', 0);
        $metrics[] = '# HELP http_requests_total Total HTTP requests';
        $metrics[] = '# TYPE http_requests_total counter';
        $metrics[] = sprintf('http_requests_total{service="inventory-service"} %d', $requests);
        
        // HTTP requests by status
        foreach (['200', '201', '400', '401', '404', '422', '500'] as $status) {
            $count = cache()->get("metrics:http_requests_status_{$status}", 0);
            $metrics[] = sprintf('http_requests_total{service="inventory-service",status="%s"} %d', $status, $count);
        }
        
        // Business metrics - Products
        $productsCreated = cache()->get('metrics:products_created_total', 0);
        $metrics[] = '# HELP products_created_total Total products created';
        $metrics[] = '# TYPE products_created_total counter';
        $metrics[] = sprintf('products_created_total{service="inventory-service"} %d', $productsCreated);
        
        $productsUpdated = cache()->get('metrics:products_updated_total', 0);
        $metrics[] = '# HELP products_updated_total Total products updated';
        $metrics[] = '# TYPE products_updated_total counter';
        $metrics[] = sprintf('products_updated_total{service="inventory-service"} %d', $productsUpdated);
        
        $stockAdjustments = cache()->get('metrics:stock_adjustments_total', 0);
        $metrics[] = '# HELP stock_adjustments_total Total stock adjustments';
        $metrics[] = '# TYPE stock_adjustments_total counter';
        $metrics[] = sprintf('stock_adjustments_total{service="inventory-service"} %d', $stockAdjustments);
        
        $lowStockProducts = cache()->get('metrics:products_low_stock', 0);
        $metrics[] = '# HELP products_low_stock Products with low stock';
        $metrics[] = '# TYPE products_low_stock gauge';
        $metrics[] = sprintf('products_low_stock{service="inventory-service"} %d', $lowStockProducts);
        
        $categoriesCreated = cache()->get('metrics:categories_created_total', 0);
        $metrics[] = '# HELP categories_created_total Total categories created';
        $metrics[] = '# TYPE categories_created_total counter';
        $metrics[] = sprintf('categories_created_total{service="inventory-service"} %d', $categoriesCreated);
        
        // Response time (simplified - using last request time)
        $responseTime = cache()->get('metrics:http_request_duration_seconds', 0.1);
        $metrics[] = '# HELP http_request_duration_seconds HTTP request duration';
        $metrics[] = '# TYPE http_request_duration_seconds gauge';
        $metrics[] = sprintf('http_request_duration_seconds{service="inventory-service"} %.3f', $responseTime);
        
        // PHP/Laravel metrics
        $metrics[] = '# HELP php_memory_usage_bytes PHP memory usage';
        $metrics[] = '# TYPE php_memory_usage_bytes gauge';
        $metrics[] = sprintf('php_memory_usage_bytes{service="inventory-service"} %d', memory_get_usage(true));
        
        return response(implode("\n", $metrics) . "\n", Response::HTTP_OK)
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }
}

