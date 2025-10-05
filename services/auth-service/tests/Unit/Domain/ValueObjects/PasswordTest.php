<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\Exceptions\InvalidPasswordException;
use Src\Domain\ValueObjects\Password;

final class PasswordTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_password_with_valid_plain_text(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';

        // Act
        $password = Password::fromPlainText($plainPassword);

        // Assert
        $this->assertInstanceOf(Password::class, $password);
        $this->assertNotEquals($plainPassword, $password->hash());
        $this->assertTrue(password_verify($plainPassword, $password->hash()));
    }

    /**
     * @test
     */
    public function it_creates_password_from_hashed_value(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        // Act
        $password = Password::fromHash($hashedPassword);

        // Assert
        $this->assertInstanceOf(Password::class, $password);
        $this->assertEquals($hashedPassword, $password->value());
    }

    /**
     * @test
     */
    public function it_hashes_password_automatically(): void
    {
        // Arrange
        $plainPassword = 'MyPassword123!';

        // Act
        $password = Password::fromPlainText($plainPassword);

        // Assert
        $hash = $password->hash(); // Call hash() method to get BCrypt hash
        $this->assertNotEquals($plainPassword, $hash);
        $this->assertStringStartsWith('$2y$', $hash); // BCrypt hash format
    }

    /**
     * @test
     */
    public function it_verifies_correct_password(): void
    {
        // Arrange
        $plainPassword = 'CorrectPassword@2024';
        $password = Password::fromPlainText($plainPassword);

        // Act
        $isValid = Password::fromPlainText($plainPassword)->matches($password->hash());

        // Assert
        $this->assertTrue($isValid);
    }

    /**
     * @test
     */
    public function it_rejects_incorrect_password(): void
    {
        // Arrange
        $plainPassword = 'CorrectPassword@2024';
        $wrongPassword = 'WrongPassword@2024';
        $password = Password::fromPlainText($plainPassword);

        // Act
        $isValid = Password::fromPlainText($wrongPassword)->matches($password->value());

        // Assert
        $this->assertFalse($isValid);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_empty_plain_password(): void
    {
        // Assert
        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        // Act
        Password::fromPlainText('');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_whitespace_only_password(): void
    {
        // Assert
        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        // Act
        Password::fromPlainText('   ');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_password_shorter_than_8_characters(): void
    {
        // Assert
        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        // Act
        Password::fromPlainText('Short1!');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_password_without_uppercase(): void
    {
        // Assert
        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must contain at least one uppercase letter');

        // Act
        Password::fromPlainText('lowercase123!');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_password_without_lowercase(): void
    {
        // Assert
        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must contain at least one lowercase letter');

        // Act
        Password::fromPlainText('UPPERCASE123!');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_password_without_number(): void
    {
        // Assert
        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must contain at least one number');

        // Act
        Password::fromPlainText('NoNumber!@#');
    }

    /**
     * @test
     */
    public function it_accepts_password_without_special_character(): void
    {
        // Arrange & Act
        $password = Password::fromPlainText('NoSpecial123Aa');

        // Assert
        $this->assertInstanceOf(Password::class, $password);
    }

    /**
     * @test
     */
    public function it_accepts_password_with_exactly_8_characters(): void
    {
        // Arrange
        $minPassword = 'Pass123!';

        // Act
        $password = Password::fromPlainText($minPassword);

        // Assert
        $this->assertInstanceOf(Password::class, $password);
        $this->assertTrue(Password::fromPlainText($minPassword)->matches($password->hash()));
    }

    /**
     * @test
     */
    public function it_accepts_various_special_characters(): void
    {
        // Arrange
        $specialChars = ['!', '@', '#', '$', '%', '^', '&', '*'];

        // Act & Assert
        foreach ($specialChars as $char) {
            $password = Password::fromPlainText("Pass123{$char}");
            $this->assertInstanceOf(Password::class, $password);
        }
    }

    /**
     * @test
     */
    public function it_creates_different_hashes_for_same_password(): void
    {
        // Arrange
        $plainPassword = 'SamePassword123!';

        // Act
        $password1 = Password::fromPlainText($plainPassword);
        $password2 = Password::fromPlainText($plainPassword);

        // Assert
        $this->assertNotEquals($password1->hash(), $password2->hash());
        $this->assertTrue(Password::fromPlainText($plainPassword)->matches($password1->hash()));
        $this->assertTrue(Password::fromPlainText($plainPassword)->matches($password2->hash()));
    }

    /**
     * @test
     */
    public function it_accepts_empty_hash_since_validation_skipped(): void
    {
        // Note: fromHash skips validation as hashes are already validated
        // Act
        $password = Password::fromHash('any-hash-value');

        // Assert
        $this->assertInstanceOf(Password::class, $password);
    }

    /**
     * @test
     */
    public function it_compares_passwords_by_hash(): void
    {
        // Arrange
        $plainPassword = 'TestPass123!';
        $password1 = Password::fromPlainText($plainPassword);
        $hashedPassword = $password1->hash();
        $password2 = Password::fromHash($hashedPassword);

        // Assert
        $this->assertFalse($password1->equals($password2)); // Plain != Hashed
        $this->assertTrue(Password::fromPlainText($plainPassword)->matches($hashedPassword));
    }
}

