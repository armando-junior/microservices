<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\Exceptions\InvalidEmailException;
use Src\Domain\ValueObjects\Email;

final class EmailTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_email_with_valid_email_address(): void
    {
        // Arrange
        $validEmail = 'test@example.com';

        // Act
        $email = new Email($validEmail);

        // Assert
        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals($validEmail, $email->value());
        $this->assertEquals($validEmail, (string) $email);
    }

    /**
     * @test
     */
    public function it_creates_email_using_from_string_factory(): void
    {
        // Arrange
        $validEmail = 'factory@example.com';

        // Act
        $email = Email::fromString($validEmail);

        // Assert
        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals($validEmail, $email->value());
    }

    /**
     * @test
     */
    public function it_normalizes_email_to_lowercase(): void
    {
        // Arrange
        $mixedCaseEmail = 'Test@Example.COM';
        $expectedEmail = 'test@example.com';

        // Act
        $email = new Email($mixedCaseEmail);

        // Assert
        $this->assertEquals($expectedEmail, $email->value());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_empty_email(): void
    {
        // Assert
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        // Act
        new Email('');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_whitespace_only_email(): void
    {
        // Assert
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        // Act
        new Email('   ');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_email_format(): void
    {
        // Assert
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Invalid email format');

        // Act
        new Email('invalid-email');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_email_without_domain(): void
    {
        // Assert
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Invalid email format');

        // Act
        new Email('test@');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_email_without_at_symbol(): void
    {
        // Assert
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('Invalid email format');

        // Act
        new Email('testexample.com');
    }

    /**
     * @test
     */
    public function it_accepts_valid_email_formats(): void
    {
        // Arrange
        $validEmails = [
            'simple@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_123@subdomain.example.com',
            '123@example.com',
        ];

        // Act & Assert
        foreach ($validEmails as $validEmail) {
            $email = new Email($validEmail);
            $this->assertInstanceOf(Email::class, $email);
        }
    }

    /**
     * @test
     */
    public function it_compares_emails_for_equality(): void
    {
        // Arrange
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('different@example.com');

        // Assert
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    /**
     * @test
     */
    public function it_compares_emails_case_insensitively(): void
    {
        // Arrange
        $email1 = new Email('Test@Example.com');
        $email2 = new Email('test@example.com');

        // Assert
        $this->assertTrue($email1->equals($email2));
    }
}

