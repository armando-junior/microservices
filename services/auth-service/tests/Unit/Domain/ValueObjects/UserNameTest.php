<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\Exceptions\InvalidUserNameException;
use Src\Domain\ValueObjects\UserName;

final class UserNameTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_username_with_valid_name(): void
    {
        // Arrange
        $validName = 'John Doe';

        // Act
        $userName = new UserName($validName);

        // Assert
        $this->assertInstanceOf(UserName::class, $userName);
        $this->assertEquals($validName, $userName->value());
        $this->assertEquals($validName, (string) $userName);
    }

    /**
     * @test
     */
    public function it_creates_username_using_from_string_factory(): void
    {
        // Arrange
        $validName = 'Jane Smith';

        // Act
        $userName = UserName::fromString($validName);

        // Assert
        $this->assertInstanceOf(UserName::class, $userName);
        $this->assertEquals($validName, $userName->value());
    }

    /**
     * @test
     */
    public function it_trims_whitespace_from_name(): void
    {
        // Arrange
        $nameWithWhitespace = '  John Doe  ';
        $expectedName = 'John Doe';

        // Act
        $userName = new UserName($nameWithWhitespace);

        // Assert
        $this->assertEquals($expectedName, $userName->value());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_empty_name(): void
    {
        // Assert
        $this->expectException(InvalidUserNameException::class);
        $this->expectExceptionMessage('User name cannot be empty');

        // Act
        new UserName('');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_whitespace_only_name(): void
    {
        // Assert
        $this->expectException(InvalidUserNameException::class);
        $this->expectExceptionMessage('User name cannot be empty');

        // Act
        new UserName('   ');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_name_shorter_than_2_characters(): void
    {
        // Assert
        $this->expectException(InvalidUserNameException::class);
        $this->expectExceptionMessage('User name must be at least 2 characters long');

        // Act
        new UserName('J');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_name_longer_than_100_characters(): void
    {
        // Assert
        $this->expectException(InvalidUserNameException::class);
        $this->expectExceptionMessage('User name cannot exceed 100 characters');

        // Act
        new UserName(str_repeat('a', 101));
    }

    /**
     * @test
     */
    public function it_accepts_name_with_exactly_2_characters(): void
    {
        // Arrange
        $minName = 'Jo';

        // Act
        $userName = new UserName($minName);

        // Assert
        $this->assertInstanceOf(UserName::class, $userName);
        $this->assertEquals($minName, $userName->value());
    }

    /**
     * @test
     */
    public function it_accepts_name_with_exactly_100_characters(): void
    {
        // Arrange
        $maxName = str_repeat('a', 100);

        // Act
        $userName = new UserName($maxName);

        // Assert
        $this->assertInstanceOf(UserName::class, $userName);
        $this->assertEquals($maxName, $userName->value());
    }

    /**
     * @test
     */
    public function it_accepts_names_with_special_characters(): void
    {
        // Arrange
        $validNames = [
            'José Silva',
            "O'Brien",
            'Jean-Luc Picard',
            'Müller Schmidt',
            // Note: Cyrillic and other non-Latin scripts not supported by current validation
        ];

        // Act & Assert
        foreach ($validNames as $validName) {
            $userName = new UserName($validName);
            $this->assertInstanceOf(UserName::class, $userName);
        }
    }

    /**
     * @test
     */
    public function it_compares_usernames_for_equality(): void
    {
        // Arrange
        $userName1 = new UserName('John Doe');
        $userName2 = new UserName('John Doe');
        $userName3 = new UserName('Jane Smith');

        // Assert
        $this->assertTrue($userName1->equals($userName2));
        $this->assertFalse($userName1->equals($userName3));
    }

    /**
     * @test
     */
    public function it_compares_usernames_case_sensitively(): void
    {
        // Arrange
        $userName1 = new UserName('John Doe');
        $userName2 = new UserName('john doe');

        // Assert
        $this->assertFalse($userName1->equals($userName2));
    }
}

