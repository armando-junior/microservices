<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use Src\Domain\ValueObjects\UserId;
use Src\Infrastructure\Auth\JWTTokenGenerator;

final class JWTTokenGeneratorTest extends TestCase
{
    private JWTTokenGenerator $tokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tokenGenerator = new JWTTokenGenerator(
            secret: 'test-secret-key-for-testing-purposes-only',
            ttl: 3600,
            issuer: 'test-auth-service'
        );
    }

    /**
     * @test
     */
    public function it_generates_a_valid_jwt_token(): void
    {
        // Arrange
        $userId = UserId::generate();

        // Act
        $token = $this->tokenGenerator->generate($userId);

        // Assert
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // JWT should have 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * @test
     */
    public function it_verifies_a_valid_token(): void
    {
        // Arrange
        $userId = UserId::generate();
        $token = $this->tokenGenerator->generate($userId);

        // Act
        $payload = $this->tokenGenerator->decode($token);

        // Assert
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('sub', $payload);
        $this->assertEquals($userId->value(), $payload['sub']);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_token(): void
    {
        // Arrange
        $invalidToken = 'invalid.token.here';

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->tokenGenerator->decode($invalidToken);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_expired_token(): void
    {
        // Arrange - Set TTL to -1 second (already expired)
        $tokenGenerator = new JWTTokenGenerator(
            secret: 'test-secret-key-for-testing-purposes-only',
            ttl: -1,
            issuer: 'test-auth-service'
        );
        
        $userId = UserId::generate();
        $token = $tokenGenerator->generate($userId);

        // Give it a moment to ensure it's expired
        sleep(1);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $tokenGenerator->decode($token);
    }

    /**
     * @test
     */
    public function it_includes_correct_issuer_in_token(): void
    {
        // Arrange
        $userId = UserId::generate();

        // Act
        $token = $this->tokenGenerator->generate($userId);
        $payload = $this->tokenGenerator->decode($token);

        // Assert
        $this->assertArrayHasKey('iss', $payload);
        $this->assertEquals('test-auth-service', $payload['iss']);
    }

    /**
     * @test
     */
    public function it_includes_subject_user_id_in_token(): void
    {
        // Arrange
        $userId = UserId::generate();

        // Act
        $token = $this->tokenGenerator->generate($userId);
        $payload = $this->tokenGenerator->decode($token);

        // Assert
        $this->assertArrayHasKey('sub', $payload);
        $this->assertEquals($userId->value(), $payload['sub']);
    }

    /**
     * @test
     */
    public function it_includes_issued_at_timestamp_in_token(): void
    {
        // Arrange
        $userId = UserId::generate();
        $beforeTimestamp = time();

        // Act
        $token = $this->tokenGenerator->generate($userId);
        $payload = $this->tokenGenerator->decode($token);
        $afterTimestamp = time();

        // Assert
        $this->assertArrayHasKey('iat', $payload);
        $this->assertGreaterThanOrEqual($beforeTimestamp, $payload['iat']);
        $this->assertLessThanOrEqual($afterTimestamp, $payload['iat']);
    }

    /**
     * @test
     */
    public function it_includes_expiration_timestamp_in_token(): void
    {
        // Arrange
        $userId = UserId::generate();
        $ttl = 3600; // 1 hour
        $tokenGenerator = new JWTTokenGenerator(
            secret: 'test-secret-key-for-testing-purposes-only',
            ttl: $ttl,
            issuer: 'test-auth-service'
        );

        // Act
        $token = $tokenGenerator->generate($userId);
        $payload = $tokenGenerator->decode($token);

        // Assert
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertEquals($ttl, $payload['exp'] - $payload['iat']);
    }

    /**
     * @test
     */
    public function it_includes_unique_jti_in_token(): void
    {
        // Arrange
        $userId = UserId::generate();

        // Act
        $token1 = $this->tokenGenerator->generate($userId);
        $token2 = $this->tokenGenerator->generate($userId);
        
        $payload1 = $this->tokenGenerator->decode($token1);
        $payload2 = $this->tokenGenerator->decode($token2);

        // Assert
        $this->assertArrayHasKey('jti', $payload1);
        $this->assertArrayHasKey('jti', $payload2);
        $this->assertNotEquals($payload1['jti'], $payload2['jti']);
    }

    /**
     * @test
     */
    public function it_returns_correct_ttl(): void
    {
        // Arrange
        $expectedTtl = 7200; // 2 hours
        $tokenGenerator = new JWTTokenGenerator(
            secret: 'test-secret-key-for-testing-purposes-only',
            ttl: $expectedTtl,
            issuer: 'test-auth-service'
        );

        // Act
        $ttl = $tokenGenerator->getTTL();

        // Assert
        $this->assertEquals($expectedTtl, $ttl);
    }

    /**
     * @test
     */
    public function it_generates_different_tokens_for_same_user(): void
    {
        // Arrange
        $userId = UserId::generate();

        // Act
        $token1 = $this->tokenGenerator->generate($userId);
        usleep(1000); // 1ms delay to ensure different timestamp
        $token2 = $this->tokenGenerator->generate($userId);

        // Assert
        $this->assertNotEquals($token1, $token2);
    }

    /**
     * @test
     */
    public function it_fails_to_verify_token_with_wrong_secret(): void
    {
        // Arrange
        $userId = UserId::generate();
        $token = $this->tokenGenerator->generate($userId);

        // Change the secret
        $differentSecretGenerator = new JWTTokenGenerator(
            secret: 'different-secret-key',
            ttl: 3600,
            issuer: 'test-auth-service'
        );

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $differentSecretGenerator->decode($token);
    }
}

