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
        $metrics[] = sprintf('app_info{service="auth-service",version="%s"} 1', config('app.version', '1.0.0'));
        
        // Up metric
        $metrics[] = '# HELP up Service is up';
        $metrics[] = '# TYPE up gauge';
        $metrics[] = 'up{service="auth-service"} 1';
        
        // HTTP requests total
        $requests = cache()->get('metrics:http_requests_total', 0);
        $metrics[] = '# HELP http_requests_total Total HTTP requests';
        $metrics[] = '# TYPE http_requests_total counter';
        $metrics[] = sprintf('http_requests_total{service="auth-service"} %d', $requests);
        
        // HTTP requests by status
        foreach (['200', '201', '400', '401', '404', '422', '500'] as $status) {
            $count = cache()->get("metrics:http_requests_status_{$status}", 0);
            $metrics[] = sprintf('http_requests_total{service="auth-service",status="%s"} %d', $status, $count);
        }
        
        // Business metrics - Authentication
        $loginAttempts = cache()->get('metrics:login_attempts_total', 0);
        $metrics[] = '# HELP login_attempts_total Total login attempts';
        $metrics[] = '# TYPE login_attempts_total counter';
        $metrics[] = sprintf('login_attempts_total{service="auth-service"} %d', $loginAttempts);
        
        $loginSuccess = cache()->get('metrics:login_success_total', 0);
        $metrics[] = '# HELP login_success_total Total successful logins';
        $metrics[] = '# TYPE login_success_total counter';
        $metrics[] = sprintf('login_success_total{service="auth-service"} %d', $loginSuccess);
        
        $loginFailed = cache()->get('metrics:login_failed_total', 0);
        $metrics[] = '# HELP login_failed_total Total failed logins';
        $metrics[] = '# TYPE login_failed_total counter';
        $metrics[] = sprintf('login_failed_total{service="auth-service"} %d', $loginFailed);
        
        $usersRegistered = cache()->get('metrics:users_registered_total', 0);
        $metrics[] = '# HELP users_registered_total Total users registered';
        $metrics[] = '# TYPE users_registered_total counter';
        $metrics[] = sprintf('users_registered_total{service="auth-service"} %d', $usersRegistered);
        
        $tokensGenerated = cache()->get('metrics:tokens_generated_total', 0);
        $metrics[] = '# HELP tokens_generated_total Total JWT tokens generated';
        $metrics[] = '# TYPE tokens_generated_total counter';
        $metrics[] = sprintf('tokens_generated_total{service="auth-service"} %d', $tokensGenerated);
        
        // Response time (simplified - using last request time)
        $responseTime = cache()->get('metrics:http_request_duration_seconds', 0.1);
        $metrics[] = '# HELP http_request_duration_seconds HTTP request duration';
        $metrics[] = '# TYPE http_request_duration_seconds gauge';
        $metrics[] = sprintf('http_request_duration_seconds{service="auth-service"} %.3f', $responseTime);
        
        // PHP/Laravel metrics
        $metrics[] = '# HELP php_memory_usage_bytes PHP memory usage';
        $metrics[] = '# TYPE php_memory_usage_bytes gauge';
        $metrics[] = sprintf('php_memory_usage_bytes{service="auth-service"} %d', memory_get_usage(true));
        
        return response(implode("\n", $metrics) . "\n", Response::HTTP_OK)
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }
}

