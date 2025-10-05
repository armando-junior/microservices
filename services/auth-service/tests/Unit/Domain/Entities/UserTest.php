<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Domain\Entities\User;
use Src\Domain\Events\UserPasswordChanged;
use Src\Domain\Events\UserRegistered;
use Src\Domain\Events\UserUpdated;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

final class UserTest extends TestCase
{
    private UserId $userId;
    private UserName $userName;
    private Email $userEmail;
    private Password $userPassword;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userId = UserId::generate();
        $this->userName = new UserName('John Doe');
        $this->userEmail = new Email('john@example.com');
        $this->userPassword = Password::fromPlainText('SecurePass@123');
    }

    /**
     * @test
     */
    public function it_creates_a_new_user(): void
    {
        // Act
        $user = User::create(
            $this->userId,
            $this->userName,
            $this->userEmail,
            $this->userPassword
        );

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->userId, $user->getId());
        $this->assertEquals($this->userName, $user->getName());
        $this->assertEquals($this->userEmail, $user->getEmail());
        $this->assertEquals($this->userPassword, $user->getPassword());
        $this->assertTrue($user->isActive());
        $this->assertInstanceOf(DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());
        $this->assertNull($user->getEmailVerifiedAt());
    }

    /**
     * @test
     */
    public function it_records_user_registered_event_when_created(): void
    {
        // Act
        $user = User::create(
            $this->userId,
            $this->userName,
            $this->userEmail,
            $this->userPassword
        );

        $events = $user->pullDomainEvents();

        // Assert
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRegistered::class, $events[0]);
    }

    /**
     * @test
     */
    public function it_reconstitutes_user_from_persistence(): void
    {
        // Arrange
        $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2024-01-02 15:30:00');
        $emailVerifiedAt = new DateTimeImmutable('2024-01-01 11:00:00');

        // Act
        $user = User::reconstitute(
            $this->userId,
            $this->userName,
            $this->userEmail,
            $this->userPassword,
            true,
            $createdAt,
            $updatedAt,
            $emailVerifiedAt
        );

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->userId, $user->getId());
        $this->assertEquals($this->userName, $user->getName());
        $this->assertEquals($this->userEmail, $user->getEmail());
        $this->assertTrue($user->isActive());
        $this->assertEquals($createdAt, $user->getCreatedAt());
        $this->assertEquals($updatedAt, $user->getUpdatedAt());
        $this->assertEquals($emailVerifiedAt, $user->getEmailVerifiedAt());
    }

    /**
     * @test
     */
    public function it_reconstitutes_user_without_domain_events(): void
    {
        // Arrange
        $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');

        // Act
        $user = User::reconstitute(
            $this->userId,
            $this->userName,
            $this->userEmail,
            $this->userPassword,
            true,
            $createdAt
        );

        $events = $user->pullDomainEvents();

        // Assert
        $this->assertCount(0, $events);
    }

    /**
     * @test
     */
    public function it_changes_user_name(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $newName = new UserName('Jane Smith');
        
        // Act
        $user->changeName($newName);

        // Assert
        $this->assertEquals($newName, $user->getName());
        $this->assertNotNull($user->getUpdatedAt());
        
        $events = $user->pullDomainEvents();
        $this->assertCount(2, $events); // UserRegistered + UserUpdated
        $this->assertInstanceOf(UserUpdated::class, $events[1]);
    }

    /**
     * @test
     */
    public function it_does_not_change_name_if_same_name_provided(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $user->pullDomainEvents(); // Clear initial events
        
        // Act
        $user->changeName($this->userName);

        // Assert
        $this->assertEquals($this->userName, $user->getName());
        $events = $user->pullDomainEvents();
        $this->assertCount(0, $events); // No new events
    }

    /**
     * @test
     */
    public function it_changes_user_email(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $user->verifyEmail(); // Verify email first
        $this->assertTrue($user->isEmailVerified());
        
        $newEmail = new Email('newemail@example.com');
        
        // Act
        $user->changeEmail($newEmail);

        // Assert
        $this->assertEquals($newEmail, $user->getEmail());
        $this->assertFalse($user->isEmailVerified()); // Email verification should be reset
        $this->assertNotNull($user->getUpdatedAt());
        
        $events = $user->pullDomainEvents();
        $this->assertCount(3, $events); // UserRegistered + UserUpdated (verify) + UserUpdated (change)
        $this->assertInstanceOf(UserUpdated::class, $events[2]);
    }

    /**
     * @test
     */
    public function it_does_not_change_email_if_same_email_provided(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $user->pullDomainEvents(); // Clear initial events
        
        // Act
        $user->changeEmail($this->userEmail);

        // Assert
        $this->assertEquals($this->userEmail, $user->getEmail());
        $events = $user->pullDomainEvents();
        $this->assertCount(0, $events); // No new events
    }

    /**
     * @test
     */
    public function it_changes_user_password(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $newPassword = Password::fromPlainText('NewSecurePass@456');
        
        // Act
        $user->changePassword($newPassword);

        // Assert
        $this->assertEquals($newPassword, $user->getPassword());
        $this->assertNotNull($user->getUpdatedAt());
        
        $events = $user->pullDomainEvents();
        $this->assertCount(2, $events); // UserRegistered + UserPasswordChanged
        $this->assertInstanceOf(UserPasswordChanged::class, $events[1]);
    }

    /**
     * @test
     */
    public function it_verifies_correct_password(): void
    {
        // Arrange
        $plainPassword = 'CorrectPass@123';
        // Simulate user from database (with hashed password)
        $user = User::reconstitute(
            $this->userId,
            $this->userName,
            $this->userEmail,
            Password::fromHash(password_hash($plainPassword, PASSWORD_BCRYPT)),
            true,
            new DateTimeImmutable()
        );
        
        // Act
        $isValid = $user->verifyPassword(Password::fromPlainText($plainPassword));

        // Assert
        $this->assertTrue($isValid);
    }

    /**
     * @test
     */
    public function it_rejects_incorrect_password(): void
    {
        // Arrange
        $plainPassword = 'CorrectPass@123';
        $wrongPassword = 'WrongPass@456';
        // Simulate user from database (with hashed password)
        $user = User::reconstitute(
            $this->userId,
            $this->userName,
            $this->userEmail,
            Password::fromHash(password_hash($plainPassword, PASSWORD_BCRYPT)),
            true,
            new DateTimeImmutable()
        );
        
        // Act
        $isValid = $user->verifyPassword(Password::fromPlainText($wrongPassword));

        // Assert
        $this->assertFalse($isValid);
    }

    /**
     * @test
     */
    public function it_activates_inactive_user(): void
    {
        // Arrange
        $user = User::reconstitute(
            $this->userId,
            $this->userName,
            $this->userEmail,
            $this->userPassword,
            false, // inactive
            new DateTimeImmutable()
        );
        
        // Act
        $user->activate();

        // Assert
        $this->assertTrue($user->isActive());
        $this->assertNotNull($user->getUpdatedAt());
        
        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserUpdated::class, $events[0]);
    }

    /**
     * @test
     */
    public function it_does_not_activate_already_active_user(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $user->pullDomainEvents(); // Clear initial events
        
        // Act
        $user->activate();

        // Assert
        $this->assertTrue($user->isActive());
        $events = $user->pullDomainEvents();
        $this->assertCount(0, $events); // No new events
    }

    /**
     * @test
     */
    public function it_deactivates_active_user(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $user->pullDomainEvents(); // Clear initial events
        
        // Act
        $user->deactivate();

        // Assert
        $this->assertFalse($user->isActive());
        $this->assertNotNull($user->getUpdatedAt());
        
        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserUpdated::class, $events[0]);
    }

    /**
     * @test
     */
    public function it_does_not_deactivate_already_inactive_user(): void
    {
        // Arrange
        $user = User::reconstitute(
            $this->userId,
            $this->userName,
            $this->userEmail,
            $this->userPassword,
            false, // inactive
            new DateTimeImmutable()
        );
        
        // Act
        $user->deactivate();

        // Assert
        $this->assertFalse($user->isActive());
        $events = $user->pullDomainEvents();
        $this->assertCount(0, $events); // No new events
    }

    /**
     * @test
     */
    public function it_verifies_user_email(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $this->assertFalse($user->isEmailVerified());
        
        // Act
        $user->verifyEmail();

        // Assert
        $this->assertTrue($user->isEmailVerified());
        $this->assertNotNull($user->getEmailVerifiedAt());
        $this->assertNotNull($user->getUpdatedAt());
        
        $events = $user->pullDomainEvents();
        $this->assertCount(2, $events); // UserRegistered + UserUpdated
        $this->assertInstanceOf(UserUpdated::class, $events[1]);
    }

    /**
     * @test
     */
    public function it_does_not_verify_email_if_already_verified(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $user->verifyEmail();
        $user->pullDomainEvents(); // Clear events
        
        // Act
        $user->verifyEmail();

        // Assert
        $this->assertTrue($user->isEmailVerified());
        $events = $user->pullDomainEvents();
        $this->assertCount(0, $events); // No new events
    }

    /**
     * @test
     */
    public function it_pulls_and_clears_domain_events(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        
        // Act
        $events1 = $user->pullDomainEvents();
        $events2 = $user->pullDomainEvents();

        // Assert
        $this->assertCount(1, $events1);
        $this->assertCount(0, $events2); // Events should be cleared after first pull
    }

    /**
     * @test
     */
    public function it_converts_to_array(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        
        // Act
        $array = $user->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals($this->userId->value(), $array['id']);
        $this->assertEquals($this->userName->value(), $array['name']);
        $this->assertEquals($this->userEmail->value(), $array['email']);
        $this->assertTrue($array['is_active']);
        $this->assertNull($array['email_verified_at']);
        $this->assertNotNull($array['created_at']);
        $this->assertNull($array['updated_at']);
    }

    /**
     * @test
     */
    public function it_converts_to_array_with_verified_email(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $user->verifyEmail();
        
        // Act
        $array = $user->toArray();

        // Assert
        $this->assertNotNull($array['email_verified_at']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $array['email_verified_at']
        );
    }

    /**
     * @test
     */
    public function it_updates_updated_at_when_modifying_user(): void
    {
        // Arrange
        $user = User::create($this->userId, $this->userName, $this->userEmail, $this->userPassword);
        $this->assertNull($user->getUpdatedAt());
        
        // Act
        $user->changeName(new UserName('New Name'));

        // Assert
        $this->assertNotNull($user->getUpdatedAt());
        $this->assertGreaterThanOrEqual(
            $user->getCreatedAt()->getTimestamp(),
            $user->getUpdatedAt()->getTimestamp()
        );
    }
}

