<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_health_check_response(): void
    {
        // Act
        $response = $this->getJson('/api/health');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'service',
                'timestamp',
            ])
            ->assertJson([
                'status' => 'ok',
                'service' => 'auth-service',
            ]);
    }

    /**
     * @test
     */
    public function health_check_does_not_require_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/health');

        // Assert
        $response->assertStatus(200);
    }
}

