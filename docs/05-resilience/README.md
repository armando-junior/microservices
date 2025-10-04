# Resiliência e Segurança

## Visão Geral

Este documento detalha as estratégias de resiliência e segurança implementadas no sistema de microserviços.

## Padrões de Resiliência

### 1. Circuit Breaker

Previne cascata de falhas interrompendo chamadas para serviços que estão falhando.

#### Implementação

```php
<?php

namespace App\Infrastructure\Resilience;

use Illuminate\Support\Facades\Redis;

class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private string $service;
    private int $failureThreshold;
    private int $timeout;
    private int $retryAfter;

    public function __construct(
        string $service,
        int $failureThreshold = 5,
        int $timeout = 30,
        int $retryAfter = 60
    ) {
        $this->service = $service;
        $this->failureThreshold = $failureThreshold;
        $this->timeout = $timeout;
        $this->retryAfter = $retryAfter;
    }

    public function call(callable $callback)
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            if ($this->shouldRetry()) {
                $this->setState(self::STATE_HALF_OPEN);
            } else {
                throw new \Exception("Circuit breaker is OPEN for {$this->service}");
            }
        }

        try {
            $result = $callback();
            $this->onSuccess();
            return $result;
        } catch (\Exception $e) {
            $this->onFailure();
            throw $e;
        }
    }

    private function getState(): string
    {
        return Redis::get($this->getStateKey()) ?? self::STATE_CLOSED;
    }

    private function setState(string $state): void
    {
        Redis::set($this->getStateKey(), $state);
    }

    private function onSuccess(): void
    {
        Redis::del($this->getFailureCountKey());
        $this->setState(self::STATE_CLOSED);
    }

    private function onFailure(): void
    {
        $failures = Redis::incr($this->getFailureCountKey());
        Redis::expire($this->getFailureCountKey(), $this->timeout);

        if ($failures >= $this->failureThreshold) {
            $this->setState(self::STATE_OPEN);
            Redis::setex($this->getOpenUntilKey(), $this->retryAfter, time() + $this->retryAfter);
        }
    }

    private function shouldRetry(): bool
    {
        $openUntil = Redis::get($this->getOpenUntilKey());
        return $openUntil && time() >= (int)$openUntil;
    }

    private function getStateKey(): string
    {
        return "circuit_breaker:{$this->service}:state";
    }

    private function getFailureCountKey(): string
    {
        return "circuit_breaker:{$this->service}:failures";
    }

    private function getOpenUntilKey(): string
    {
        return "circuit_breaker:{$this->service}:open_until";
    }
}
```

#### Uso

```php
$circuitBreaker = new CircuitBreaker('inventory-service');

try {
    $result = $circuitBreaker->call(function () {
        return $this->httpClient->get('http://inventory-service/api/products/123');
    });
} catch (\Exception $e) {
    // Fallback logic
    $result = $this->getCachedProduct(123);
}
```

### 2. Retry Pattern

Tentativas automáticas com backoff exponencial.

#### Implementação

```php
<?php

namespace App\Infrastructure\Resilience;

class RetryHandler
{
    private int $maxAttempts;
    private int $initialDelay;
    private float $multiplier;
    private int $maxDelay;

    public function __construct(
        int $maxAttempts = 3,
        int $initialDelay = 1000, // milliseconds
        float $multiplier = 2.0,
        int $maxDelay = 10000 // milliseconds
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->initialDelay = $initialDelay;
        $this->multiplier = $multiplier;
        $this->maxDelay = $maxDelay;
    }

    public function execute(callable $callback, ?callable $shouldRetry = null)
    {
        $attempt = 0;
        $delay = $this->initialDelay;

        while ($attempt < $this->maxAttempts) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $this->maxAttempts) {
                    throw $e;
                }

                if ($shouldRetry && !$shouldRetry($e)) {
                    throw $e;
                }

                \Log::warning("Retry attempt {$attempt}/{$this->maxAttempts}", [
                    'exception' => $e->getMessage(),
                    'delay_ms' => $delay,
                ]);

                usleep($delay * 1000);
                $delay = min($delay * $this->multiplier, $this->maxDelay);
            }
        }
    }
}
```

#### Uso

```php
$retryHandler = new RetryHandler(maxAttempts: 3);

$result = $retryHandler->execute(
    callback: fn() => $this->httpClient->post('http://payment-gateway/process', $data),
    shouldRetry: fn(\Exception $e) => $e instanceof NetworkException
);
```

### 3. Timeout

Limites de tempo para operações.

```php
<?php

namespace App\Infrastructure\Resilience;

class TimeoutHandler
{
    public function execute(callable $callback, int $timeoutSeconds)
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            throw new \RuntimeException('Could not fork process');
        }

        if ($pid) {
            // Parent process
            $status = null;
            $timeout = time() + $timeoutSeconds;

            while (time() < $timeout) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                if ($res == -1 || $res > 0) {
                    return true;
                }

                sleep(1);
            }

            // Timeout reached
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
            throw new \Exception("Operation timed out after {$timeoutSeconds} seconds");
        } else {
            // Child process
            $callback();
            exit(0);
        }
    }
}
```

### 4. Bulkhead

Isolamento de recursos para prevenir falhas em cascata.

```php
<?php

namespace App\Infrastructure\Resilience;

use Illuminate\Support\Facades\Redis;

class Bulkhead
{
    private string $name;
    private int $maxConcurrent;

    public function __construct(string $name, int $maxConcurrent = 10)
    {
        $this->name = $name;
        $this->maxConcurrent = $maxConcurrent;
    }

    public function execute(callable $callback)
    {
        if (!$this->acquire()) {
            throw new \Exception("Bulkhead limit reached for {$this->name}");
        }

        try {
            return $callback();
        } finally {
            $this->release();
        }
    }

    private function acquire(): bool
    {
        $key = "bulkhead:{$this->name}:count";
        $current = Redis::incr($key);

        if ($current > $this->maxConcurrent) {
            Redis::decr($key);
            return false;
        }

        return true;
    }

    private function release(): void
    {
        $key = "bulkhead:{$this->name}:count";
        Redis::decr($key);
    }
}
```

### 5. Rate Limiting

Controle de taxa de requisições.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class RateLimiter
{
    public function handle($request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $this->availableIn($key),
            ], 429);
        }

        $this->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->retriesLeft($key, $maxAttempts)
        );
    }

    protected function resolveRequestSignature($request): string
    {
        return sha1(
            $request->method() .
            '|' . $request->server('SERVER_NAME') .
            '|' . $request->path() .
            '|' . $request->ip()
        );
    }

    protected function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return Redis::get($key) >= $maxAttempts;
    }

    protected function hit(string $key, int $decaySeconds): int
    {
        Redis::incr($key);
        Redis::expire($key, $decaySeconds);
        return (int) Redis::get($key);
    }

    protected function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = (int) Redis::get($key);
        return $maxAttempts - $attempts;
    }

    protected function availableIn(string $key): int
    {
        return (int) Redis::ttl($key);
    }

    protected function addHeaders($response, int $maxAttempts, int $retriesLeft)
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $retriesLeft),
        ]);

        return $response;
    }
}
```

## Segurança

### 1. JWT Authentication

```php
<?php

namespace App\Infrastructure\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtTokenGenerator
{
    private string $secret;
    private int $ttl;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->ttl = config('jwt.ttl', 3600);
    }

    public function generate(User $user): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->getUserId()->value(),
            'iat' => time(),
            'exp' => time() + $this->ttl,
            'data' => [
                'user_id' => $user->getUserId()->value(),
                'email' => $user->getEmail()->value(),
                'roles' => array_map(fn($role) => $role->getSlug(), $user->getRoles()),
            ],
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function validate(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }
}
```

### 2. API Key Authentication

```php
<?php

namespace App\Http\Middleware;

use Closure;

class ApiKeyMiddleware
{
    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json(['error' => 'API Key required'], 401);
        }

        if (!$this->isValidApiKey($apiKey)) {
            return response()->json(['error' => 'Invalid API Key'], 403);
        }

        return $next($request);
    }

    private function isValidApiKey(string $apiKey): bool
    {
        // Validate against database or cache
        return Redis::exists("api_keys:{$apiKey}");
    }
}
```

### 3. Request Signing

```php
<?php

namespace App\Infrastructure\Security;

class RequestSigner
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function sign(array $data, int $timestamp): string
    {
        $payload = json_encode($data) . $timestamp;
        return hash_hmac('sha256', $payload, $this->secret);
    }

    public function verify(array $data, int $timestamp, string $signature): bool
    {
        $expectedSignature = $this->sign($data, $timestamp);
        return hash_equals($expectedSignature, $signature);
    }
}
```

### 4. Data Encryption

```php
<?php

namespace App\Infrastructure\Security;

class DataEncryptor
{
    private string $key;
    private string $cipher = 'aes-256-gcm';

    public function __construct()
    {
        $this->key = config('app.key');
    }

    public function encrypt(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt(string $data): string
    {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $encrypted = substr($data, $ivLength + 16);

        return openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
}
```

## Health Checks

### Endpoint

```php
<?php

// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => checkDatabase(),
        'redis' => checkRedis(),
        'rabbitmq' => checkRabbitMQ(),
        'storage' => checkStorage(),
    ];

    $healthy = !in_array('fail', $checks);

    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'service' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
        'checks' => $checks,
    ], $healthy ? 200 : 503);
});

function checkDatabase(): string
{
    try {
        DB::connection()->getPdo();
        return 'ok';
    } catch (\Exception $e) {
        return 'fail';
    }
}

function checkRedis(): string
{
    try {
        Redis::ping();
        return 'ok';
    } catch (\Exception $e) {
        return 'fail';
    }
}

function checkRabbitMQ(): string
{
    try {
        $connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password')
        );
        $connection->close();
        return 'ok';
    } catch (\Exception $e) {
        return 'fail';
    }
}

function checkStorage(): string
{
    try {
        $testFile = storage_path('app/health_check.txt');
        file_put_contents($testFile, 'test');
        unlink($testFile);
        return 'ok';
    } catch (\Exception $e) {
        return 'fail';
    }
}
```

## Logging e Auditoria

### Structured Logging

```php
<?php

namespace App\Infrastructure\Logging;

use Illuminate\Support\Facades\Log;

class StructuredLogger
{
    public static function logRequest($request, $response, float $duration): void
    {
        Log::info('HTTP Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->status(),
            'duration_ms' => round($duration * 1000, 2),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public static function logEvent(string $event, array $data): void
    {
        Log::info("Event: {$event}", array_merge($data, [
            'timestamp' => now()->toIso8601String(),
            'service' => config('app.name'),
        ]));
    }

    public static function logError(\Exception $e, array $context = []): void
    {
        Log::error($e->getMessage(), array_merge($context, [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]));
    }
}
```

### Audit Trail

```php
<?php

namespace App\Infrastructure\Audit;

class AuditLogger
{
    public function log(
        string $action,
        string $resourceType,
        ?string $resourceId,
        ?string $userId,
        array $changes = []
    ): void {
        DB::table('audit_logs')->insert([
            'id' => Str::uuid(),
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'changes' => json_encode($changes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
```

## Monitoramento

### Métricas Prometheus

```php
<?php

namespace App\Http\Controllers;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    public function index(CollectorRegistry $registry)
    {
        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return response($result, 200, [
            'Content-Type' => RenderTextFormat::MIME_TYPE,
        ]);
    }
}
```

### Custom Metrics

```php
<?php

namespace App\Infrastructure\Monitoring;

use Prometheus\CollectorRegistry;

class Metrics
{
    private CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function incrementCounter(string $name, array $labels = []): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            config('app.name'),
            $name,
            'help',
            array_keys($labels)
        );

        $counter->inc(array_values($labels));
    }

    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            config('app.name'),
            $name,
            'help',
            array_keys($labels)
        );

        $histogram->observe($value, array_values($labels));
    }
}
```

---

**Próximo:** [Planejamento de Sprints](../06-sprints/README.md)

