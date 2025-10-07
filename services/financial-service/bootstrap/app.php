<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Remove CSRF verification from API routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        // Register custom middleware aliases
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtAuthMiddleware::class,
        ]);
        
        // Ensure API requests expect JSON and handle CORS
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\MetricsMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log all exceptions
        $exceptions->report(function (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        });
        
        // Custom JSON error rendering for API routes (with detailed errors in debug mode)
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Handle ValidationException specially
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'errors' => $e->errors(),
                    ], 422);
                }
                
                // Determine status code
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                $response = [
                    'error' => class_basename($e),
                    'message' => $e->getMessage(),
                ];
                
                // Include detailed debug info if APP_DEBUG is true
                if (config('app.debug')) {
                    $response['exception'] = get_class($e);
                    $response['file'] = $e->getFile();
                    $response['line'] = $e->getLine();
                    $response['trace'] = collect($e->getTrace())->take(10)->map(function ($trace) {
                        return [
                            'file' => $trace['file'] ?? null,
                            'line' => $trace['line'] ?? null,
                            'function' => $trace['function'] ?? null,
                            'class' => $trace['class'] ?? null,
                        ];
                    })->toArray();
                }
                
                return response()->json($response, $status);
            }
        });
        
        // Force JSON responses for API routes
        $exceptions->shouldRenderJsonWhen(function ($request, $throwable) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
