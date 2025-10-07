<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MetricsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Increment total requests
        cache()->increment('metrics:http_requests_total', 1);
        
        $response = $next($request);
        
        // Record response time
        $duration = microtime(true) - $startTime;
        cache()->put('metrics:http_request_duration_seconds', $duration, now()->addMinutes(5));
        
        // Increment status-specific counter
        $status = $response->getStatusCode();
        $statusGroup = (int) ($status / 100) * 100; // 200, 400, 500, etc.
        cache()->increment("metrics:http_requests_status_{$status}", 1);
        cache()->increment("metrics:http_requests_status_group_{$statusGroup}", 1);
        
        return $response;
    }
}

