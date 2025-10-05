<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use Mockery;
use PHPUnit\Framework\TestCase;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\Contracts\TokenGeneratorInterface;
use Src\Application\DTOs\AuthTokenDTO;
use Src\Application\DTOs\RegisterUserDTO;
use Src\Application\Exceptions\EmailAlreadyExistsException;
use Src\Application\UseCases\RegisterUser\RegisterUserUseCase;
use Src\Domain\Entities\User;
use Src\Domain\Events\UserRegistered;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

final class RegisterUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private EventPublisherInterface $eventPublisher;
    private TokenGeneratorInterface $tokenGenerator;
    private RegisterUserUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->eventPublisher = Mockery::mock(EventPublisherInterface::class);
        $this->tokenGenerator = Mockery::mock(TokenGeneratorInterface::class);
        
        $this->useCase = new RegisterUserUseCase(
            $this->userRepository,
            $this->eventPublisher,
            $this->tokenGenerator
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
    public function it_registers_a_new_user_successfully(): void
    {
        // Arrange
        $dto = new RegisterUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'SecurePass@123'
        );

        $this->userRepository
            ->shouldReceive('existsByEmail')
            ->once()
            ->with(Mockery::on(function ($email) {
                return $email instanceof Email && $email->value() === 'john@example.com';
            }))
            ->andReturnFalse();

        $this->userRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function ($user) {
                return $user instanceof User
                    && $user->getName()->value() === 'John Doe'
                    && $user->getEmail()->value() === 'john@example.com'
                    && $user->isActive();
            }))
            ->andReturnUsing(function ($user) {
                return $user;
            });

        $this->eventPublisher
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(UserRegistered::class));

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->with(Mockery::type(UserId::class))
            ->andReturn('jwt-token-here');

        $this->tokenGenerator
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(3600);

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(AuthTokenDTO::class, $result);
        $this->assertEquals('jwt-token-here', $result->accessToken);
        $this->assertEquals('bearer', $result->tokenType);
        $this->assertEquals(3600, $result->expiresIn);
        $this->assertEquals('john@example.com', $result->user->email);
        $this->assertEquals('John Doe', $result->user->name);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_email_already_exists(): void
    {
        // Arrange
        $dto = new RegisterUserDTO(
            name: 'John Doe',
            email: 'existing@example.com',
            password: 'SecurePass@123'
        );

        $existingUser = User::create(
            UserId::generate(),
            new UserName('Existing User'),
            new Email('existing@example.com'),
            Password::fromPlainText('SomePass@123')
        );

        $this->userRepository
            ->shouldReceive('existsByEmail')
            ->once()
            ->with(Mockery::on(function ($email) {
                return $email instanceof Email && $email->value() === 'existing@example.com';
            }))
            ->andReturnTrue();

        $this->userRepository
            ->shouldNotReceive('save');

        $this->eventPublisher
            ->shouldNotReceive('publish');

        $this->tokenGenerator
            ->shouldNotReceive('generate');

        // Assert
        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage('Email already exists: existing@example.com');

        // Act
        $this->useCase->execute($dto);
    }

    /**
     * @test
     */
    public function it_hashes_password_before_saving(): void
    {
        // Arrange
        $dto = new RegisterUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'PlainPassword@123'
        );

        $this->userRepository
            ->shouldReceive('existsByEmail')
            ->once()
            ->andReturnFalse();

        $savedPassword = null;
        $this->userRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function ($user) use (&$savedPassword) {
                $savedPassword = $user->getPassword()->value();
                return true;
            }))
            ->andReturnUsing(function ($user) {
                return $user;
            });

        $this->eventPublisher
            ->shouldReceive('publish')
            ->once();

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn('token');

        $this->tokenGenerator
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(3600);

        // Act
        $this->useCase->execute($dto);

        // Assert - Password VO stores plain text, hash is generated by Repository
        $this->assertEquals('PlainPassword@123', $savedPassword);
    }

    /**
     * @test
     */
    public function it_publishes_user_registered_event(): void
    {
        // Arrange
        $dto = new RegisterUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'SecurePass@123'
        );

        $this->userRepository
            ->shouldReceive('existsByEmail')
            ->once()
            ->andReturnFalse();

        $this->userRepository
            ->shouldReceive('save')
            ->once()
            ->andReturnUsing(function ($user) {
                return $user;
            });

        $publishedEvent = null;
        $this->eventPublisher
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function ($event) use (&$publishedEvent) {
                $publishedEvent = $event;
                return $event instanceof UserRegistered;
            }));

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn('token');

        $this->tokenGenerator
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(3600);

        // Act
        $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(UserRegistered::class, $publishedEvent);
    }

    /**
     * @test
     */
    public function it_generates_jwt_token_for_registered_user(): void
    {
        // Arrange
        $dto = new RegisterUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'SecurePass@123'
        );

        $this->userRepository
            ->shouldReceive('existsByEmail')
            ->once()
            ->andReturnFalse();

        $savedUserId = null;
        $this->userRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function ($user) use (&$savedUserId) {
                $savedUserId = $user->getId();
                return true;
            }))
            ->andReturnUsing(function ($user) {
                return $user;
            });

        $this->eventPublisher
            ->shouldReceive('publish')
            ->once();

        $tokenGeneratedForUserId = null;
        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->with(Mockery::on(function ($userId) use (&$tokenGeneratedForUserId) {
                $tokenGeneratedForUserId = $userId;
                return $userId instanceof UserId;
            }))
            ->andReturn('generated-jwt-token');

        $this->tokenGenerator
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(7200);

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('generated-jwt-token', $result->accessToken);
        $this->assertEquals(7200, $result->expiresIn);
        $this->assertEquals($savedUserId, $tokenGeneratedForUserId);
    }

    /**
     * @test
     */
    public function it_creates_user_as_active_by_default(): void
    {
        // Arrange
        $dto = new RegisterUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'SecurePass@123'
        );

        $this->userRepository
            ->shouldReceive('existsByEmail')
            ->once()
            ->andReturnFalse();

        $isActive = null;
        $this->userRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function ($user) use (&$isActive) {
                $isActive = $user->isActive();
                return true;
            }))
            ->andReturnUsing(function ($user) {
                return $user;
            });

        $this->eventPublisher
            ->shouldReceive('publish')
            ->once();

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn('token');

        $this->tokenGenerator
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(3600);

        // Act
        $this->useCase->execute($dto);

        // Assert
        $this->assertTrue($isActive);
    }
}

