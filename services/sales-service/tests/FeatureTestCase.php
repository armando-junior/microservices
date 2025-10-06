<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Firebase\JWT\JWT;

/**
 * Feature Test Case
 * 
 * Base class para testes de feature (HTTP endpoints).
 */
abstract class FeatureTestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected string $jwtToken;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate:fresh');
        
        // Generate a valid JWT token for tests
        $this->jwtToken = $this->generateTestJWT();
    }

    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    /**
     * Generate a valid JWT token for testing
     */
    protected function generateTestJWT(string $userId = '550e8400-e29b-41d4-a716-446655440000'): string
    {
        $payload = [
            'iss' => config('jwt.issuer', 'auth-service'),
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo', 'HS256'));
    }

    /**
     * Make an authenticated GET request
     */
    protected function authGet(string $uri, array $headers = [])
    {
        return $this->withHeaders(array_merge([
            'Authorization' => 'Bearer ' . $this->jwtToken,
            'Accept' => 'application/json',
        ], $headers))->get($uri);
    }

    /**
     * Make an authenticated POST request
     */
    protected function authPost(string $uri, array $data = [], array $headers = [])
    {
        return $this->withHeaders(array_merge([
            'Authorization' => 'Bearer ' . $this->jwtToken,
            'Accept' => 'application/json',
        ], $headers))->post($uri, $data);
    }

    /**
     * Make an authenticated PUT request
     */
    protected function authPut(string $uri, array $data = [], array $headers = [])
    {
        return $this->withHeaders(array_merge([
            'Authorization' => 'Bearer ' . $this->jwtToken,
            'Accept' => 'application/json',
        ], $headers))->put($uri, $data);
    }

    /**
     * Make an authenticated DELETE request
     */
    protected function authDelete(string $uri, array $data = [], array $headers = [])
    {
        return $this->withHeaders(array_merge([
            'Authorization' => 'Bearer ' . $this->jwtToken,
            'Accept' => 'application/json',
        ], $headers))->delete($uri, $data);
    }
}
