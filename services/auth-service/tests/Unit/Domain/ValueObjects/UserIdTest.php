<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\Exceptions\InvalidUserIdException;
use Src\Domain\ValueObjects\UserId;

final class UserIdTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_userid_with_valid_uuid(): void
    {
        // Arrange
        $validUuid = '550e8400-e29b-41d4-a716-446655440000';

        // Act
        $userId = UserId::fromString($validUuid);

        // Assert
        $this->assertInstanceOf(UserId::class, $userId);
        $this->assertEquals($validUuid, $userId->value());
        $this->assertEquals($validUuid, (string) $userId);
    }

    /**
     * @test
     */
    public function it_creates_userid_using_from_string_factory(): void
    {
        // Arrange
        $validUuid = '123e4567-e89b-12d3-a456-426614174000';

        // Act
        $userId = UserId::fromString($validUuid);

        // Assert
        $this->assertInstanceOf(UserId::class, $userId);
        $this->assertEquals($validUuid, $userId->value());
    }

    /**
     * @test
     */
    public function it_generates_new_uuid_v4(): void
    {
        // Act
        $userId = UserId::generate();

        // Assert
        $this->assertInstanceOf(UserId::class, $userId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $userId->value()
        );
    }

    /**
     * @test
     */
    public function it_generates_unique_uuids(): void
    {
        // Act
        $userId1 = UserId::generate();
        $userId2 = UserId::generate();

        // Assert
        $this->assertNotEquals($userId1->value(), $userId2->value());
    }

    /**
     * @test
     */
    public function it_normalizes_uuid_to_lowercase(): void
    {
        // Arrange
        $mixedCaseUuid = '550E8400-E29B-41D4-A716-446655440000';
        $expectedUuid = '550e8400-e29b-41d4-a716-446655440000';

        // Act
        $userId = UserId::fromString($mixedCaseUuid);

        // Assert
        $this->assertEquals($expectedUuid, $userId->value());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_empty_uuid(): void
    {
        // Assert
        $this->expectException(InvalidUserIdException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        // Act
        UserId::fromString('');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_whitespace_only_uuid(): void
    {
        // Assert
        $this->expectException(InvalidUserIdException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        // Act
        UserId::fromString('   ');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_uuid_format(): void
    {
        // Assert
        $this->expectException(InvalidUserIdException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        // Act
        UserId::fromString('not-a-valid-uuid');
    }

    /**
     * @test
     */
    public function it_accepts_any_valid_uuid_version(): void
    {
        // Note: Current implementation accepts any valid UUID format
        // Not strictly enforcing UUID v4, which is acceptable for most use cases
        
        // Arrange: UUID v1 (time-based) - still a valid UUID
        $uuidV1 = 'c232ab00-9414-11ec-b909-0242ac120002';

        // Act
        $userId = UserId::fromString($uuidV1);

        // Assert
        $this->assertInstanceOf(UserId::class, $userId);
    }

    /**
     * @test
     */
    public function it_accepts_uuid_v4_with_uppercase(): void
    {
        // Arrange
        $upperCaseUuid = '550E8400-E29B-41D4-A716-446655440000';

        // Act
        $userId = UserId::fromString($upperCaseUuid);

        // Assert
        $this->assertInstanceOf(UserId::class, $userId);
    }

    /**
     * @test
     */
    public function it_rejects_uuid_without_hyphens(): void
    {
        // Assert
        $this->expectException(InvalidUserIdException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        // Act
        UserId::fromString('550e8400e29b41d4a716446655440000');
    }

    /**
     * @test
     */
    public function it_compares_userids_for_equality(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $userId1 = UserId::fromString($uuid);
        $userId2 = UserId::fromString($uuid);
        $userId3 = UserId::generate();

        // Assert
        $this->assertTrue($userId1->equals($userId2));
        $this->assertFalse($userId1->equals($userId3));
    }

    /**
     * @test
     */
    public function it_compares_userids_case_insensitively(): void
    {
        // Arrange
        $userId1 = UserId::fromString('550E8400-E29B-41D4-A716-446655440000');
        $userId2 = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');

        // Assert
        $this->assertTrue($userId1->equals($userId2));
    }
}

