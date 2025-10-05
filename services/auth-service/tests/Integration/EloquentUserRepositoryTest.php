<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Src\Domain\Entities\User;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;
use Src\Infrastructure\Persistence\Eloquent\EloquentUserRepository;

final class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentUserRepository();
    }

    /**
     * @test
     */
    public function it_saves_a_user_successfully(): void
    {
        // Arrange
        $user = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText('SecurePass@123')
        );

        // Act
        $this->repository->save($user);

        // Assert
        $this->assertDatabaseHas('users', [
            'id' => $user->getId()->value(),
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
        ]);
        
        // Verify we can retrieve it
        $retrievedUser = $this->repository->findById($user->getId());
        $this->assertNotNull($retrievedUser);
        $this->assertEquals($user->getId()->value(), $retrievedUser->getId()->value());
    }

    /**
     * @test
     */
    public function it_finds_a_user_by_id(): void
    {
        // Arrange
        $user = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText('SecurePass@123')
        );
        $this->repository->save($user);

        // Act
        $foundUser = $this->repository->findById($user->getId());

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->getId()->value(), $foundUser->getId()->value());
        $this->assertEquals('John Doe', $foundUser->getName()->value());
        $this->assertEquals('john@example.com', $foundUser->getEmail()->value());
    }

    /**
     * @test
     */
    public function it_returns_null_when_user_not_found_by_id(): void
    {
        // Arrange
        $nonExistentId = UserId::generate();

        // Act
        $foundUser = $this->repository->findById($nonExistentId);

        // Assert
        $this->assertNull($foundUser);
    }

    /**
     * @test
     */
    public function it_finds_a_user_by_email(): void
    {
        // Arrange
        $user = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText('SecurePass@123')
        );
        $this->repository->save($user);

        // Act
        $foundUser = $this->repository->findByEmail(new Email('john@example.com'));

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertEquals('john@example.com', $foundUser->getEmail()->value());
        $this->assertEquals('John Doe', $foundUser->getName()->value());
    }

    /**
     * @test
     */
    public function it_returns_null_when_user_not_found_by_email(): void
    {
        // Arrange
        $nonExistentEmail = new Email('nonexistent@example.com');

        // Act
        $foundUser = $this->repository->findByEmail($nonExistentEmail);

        // Assert
        $this->assertNull($foundUser);
    }

    /**
     * @test
     */
    public function it_checks_if_email_exists(): void
    {
        // Arrange
        $user = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText('SecurePass@123')
        );
        $this->repository->save($user);

        // Act & Assert
        $this->assertTrue($this->repository->existsByEmail(new Email('john@example.com')));
        $this->assertFalse($this->repository->existsByEmail(new Email('nonexistent@example.com')));
    }

    /**
     * @test
     */
    public function it_updates_an_existing_user(): void
    {
        // Arrange
        $user = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText('SecurePass@123')
        );
        $this->repository->save($user);

        // Act - Update user name
        $user->changeName(new UserName('Jane Doe'));
        $this->repository->save($user);

        // Assert
        $updatedUser = $this->repository->findById($user->getId());
        $this->assertEquals('Jane Doe', $updatedUser->getName()->value());
    }

    /**
     * @test
     */
    public function it_deletes_a_user(): void
    {
        // Arrange
        $user = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText('SecurePass@123')
        );
        $this->repository->save($user);

        // Act
        $this->repository->delete($user->getId());

        // Assert
        $this->assertDatabaseMissing('users', [
            'id' => $user->getId()->value(),
        ]);
    }

    /**
     * @test
     */
    public function it_hashes_password_when_saving_user(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';
        $user = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText($plainPassword)
        );

        // Act
        $this->repository->save($user);

        // Assert
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);

        // Get saved user from database
        $savedUser = \Src\Infrastructure\Persistence\Eloquent\Models\UserModel::where('email', 'john@example.com')->first();
        
        // Password should be hashed
        $this->assertNotEquals($plainPassword, $savedUser->password);
        $this->assertTrue(password_verify($plainPassword, $savedUser->password));
    }

    /**
     * @test
     */
    public function it_finds_user_case_insensitively_by_email(): void
    {
        // Arrange
        $user = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText('SecurePass@123')
        );
        $this->repository->save($user);

        // Act
        $foundUser1 = $this->repository->findByEmail(new Email('JOHN@EXAMPLE.COM'));
        $foundUser2 = $this->repository->findByEmail(new Email('John@Example.Com'));

        // Assert
        $this->assertNotNull($foundUser1);
        $this->assertNotNull($foundUser2);
        $this->assertEquals($user->getId()->value(), $foundUser1->getId()->value());
        $this->assertEquals($user->getId()->value(), $foundUser2->getId()->value());
    }
}

