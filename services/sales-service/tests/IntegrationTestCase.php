<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Integration Test Case
 * 
 * Base class para testes de integração que usam banco de dados.
 * 
 * Note: RefreshDatabase trait handles database migrations automatically.
 * Do NOT call migrate:fresh manually as it causes VACUUM errors in CI.
 */
abstract class IntegrationTestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }
}
