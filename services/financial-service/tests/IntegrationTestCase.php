<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Integration Test Case
 * 
 * Base class para testes de integração que usam banco de dados.
 * 
 * Uses DatabaseMigrations trait which runs migrations before each test
 * and rolls back after. This is more suitable for integration tests
 * and avoids transaction conflicts in CI environments.
 */
abstract class IntegrationTestCase extends BaseTestCase
{
    use DatabaseMigrations;

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
