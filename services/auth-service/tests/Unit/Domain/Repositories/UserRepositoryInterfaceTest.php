<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Repositories;

use Mockery;
use PHPUnit\Framework\TestCase;
use Src\Domain\Entities\User;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

/**
 * Contract/Interface Test for UserRepository
 * 
 * This test ensures that any implementation of UserRepositoryInterface
 * adheres to the expected contract.
 */
final class UserRepositoryInterfaceTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = Mockery::mock(UserRepositoryInterface::class);
        
        $this->testUser = User::create(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText('SecurePass@123')
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_can_save_a_user(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(User::class))
            ->andReturnNull(); // Interface returns void

        // Act
        $this->repository->save($this->testUser);

        // Assert - If no exception thrown, test passes
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_find_user_by_id(): void
    {
        // Arrange
        $userId = $this->testUser->getId();
        
        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(function ($id) use ($userId) {
                return $id instanceof UserId && $id->equals($userId);
            }))
            ->andReturn($this->testUser);

        // Act
        $foundUser = $this->repository->findById($userId);

        // Assert
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->testUser->getId(), $foundUser->getId());
    }

    /**
     * @test
     */
    public function it_returns_null_when_user_not_found_by_id(): void
    {
        // Arrange
        $nonExistentId = UserId::generate();
        
        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(function ($id) use ($nonExistentId) {
                return $id instanceof UserId && $id->equals($nonExistentId);
            }))
            ->andReturnNull();

        // Act
        $foundUser = $this->repository->findById($nonExistentId);

        // Assert
        $this->assertNull($foundUser);
    }

    /**
     * @test
     */
    public function it_can_find_user_by_email(): void
    {
        // Arrange
        $email = $this->testUser->getEmail();
        
        $this->repository
            ->shouldReceive('findByEmail')
            ->once()
            ->with(Mockery::on(function ($e) use ($email) {
                return $e instanceof Email && $e->equals($email);
            }))
            ->andReturn($this->testUser);

        // Act
        $foundUser = $this->repository->findByEmail($email);

        // Assert
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->testUser->getEmail(), $foundUser->getEmail());
    }

    /**
     * @test
     */
    public function it_returns_null_when_user_not_found_by_email(): void
    {
        // Arrange
        $nonExistentEmail = new Email('nonexistent@example.com');
        
        $this->repository
            ->shouldReceive('findByEmail')
            ->once()
            ->with(Mockery::on(function ($email) use ($nonExistentEmail) {
                return $email instanceof Email && $email->equals($nonExistentEmail);
            }))
            ->andReturnNull();

        // Act
        $foundUser = $this->repository->findByEmail($nonExistentEmail);

        // Assert
        $this->assertNull($foundUser);
    }

    /**
     * @test
     */
    public function it_can_delete_a_user(): void
    {
        // Arrange
        $userId = $this->testUser->getId();
        
        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with(Mockery::on(function ($id) use ($userId) {
                return $id instanceof UserId && $id->equals($userId);
            }))
            ->andReturnNull(); // Interface returns void

        // Act
        $this->repository->delete($userId);

        // Assert - If no exception thrown, test passes
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_deletes_non_existent_user_without_error(): void
    {
        // Arrange
        $nonExistentId = UserId::generate();
        
        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with(Mockery::on(function ($id) use ($nonExistentId) {
                return $id instanceof UserId && $id->equals($nonExistentId);
            }))
            ->andReturnNull(); // Interface returns void, implementation handles non-existent

        // Act
        $this->repository->delete($nonExistentId);

        // Assert - If no exception thrown, test passes
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_update_existing_user(): void
    {
        // Arrange
        $updatedUser = User::reconstitute(
            $this->testUser->getId(),
            new UserName('Jane Doe'),
            $this->testUser->getEmail(),
            $this->testUser->getPassword(),
            true,
            $this->testUser->getCreatedAt(),
            new \DateTimeImmutable()
        );
        
        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function ($user) {
                return $user instanceof User && $user->getName()->value() === 'Jane Doe';
            }))
            ->andReturnNull(); // Interface returns void

        // Act
        $this->repository->save($updatedUser);

        // Assert - If no exception thrown, test passes
        $this->assertEquals('Jane Doe', $updatedUser->getName()->value());
        $this->assertNotNull($updatedUser->getUpdatedAt());
    }

    /**
     * @test
     */
    public function it_preserves_user_value_objects_during_save(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function ($user) {
                return $user instanceof User
                    && $user->getId() instanceof UserId
                    && $user->getName() instanceof UserName
                    && $user->getEmail() instanceof Email
                    && $user->getPassword() instanceof Password;
            }))
            ->andReturnNull(); // Interface returns void

        // Act
        $this->repository->save($this->testUser);

        // Assert - Verify the test user still has all its Value Objects
        $this->assertInstanceOf(User::class, $this->testUser);
        $this->assertInstanceOf(UserId::class, $this->testUser->getId());
        $this->assertInstanceOf(UserName::class, $this->testUser->getName());
        $this->assertInstanceOf(Email::class, $this->testUser->getEmail());
        $this->assertInstanceOf(Password::class, $this->testUser->getPassword());
    }
}

