<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use Mockery;
use PHPUnit\Framework\TestCase;
use Src\Application\Contracts\TokenGeneratorInterface;
use Src\Application\DTOs\AuthTokenDTO;
use Src\Application\DTOs\LoginUserDTO;
use Src\Application\Exceptions\InvalidCredentialsException;
use Src\Application\Exceptions\UserNotFoundException;
use Src\Application\UseCases\LoginUser\LoginUserUseCase;
use Src\Domain\Entities\User;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

final class LoginUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private TokenGeneratorInterface $tokenGenerator;
    private LoginUserUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->tokenGenerator = Mockery::mock(TokenGeneratorInterface::class);
        
        $this->useCase = new LoginUserUseCase(
            $this->userRepository,
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
    public function it_authenticates_user_with_valid_credentials(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';
        $dto = new LoginUserDTO(
            email: 'john@example.com',
            password: $plainPassword
        );

        // Simulate user from database (with hashed password)
        $user = User::reconstitute(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromHash(password_hash($plainPassword, PASSWORD_BCRYPT)),
            true,
            new \DateTimeImmutable()
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with(Mockery::on(function ($email) {
                return $email instanceof Email && $email->value() === 'john@example.com';
            }))
            ->andReturn($user);

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->with(
                Mockery::on(function ($userId) use ($user) {
                    return $userId instanceof UserId && $userId->equals($user->getId());
                }),
                Mockery::on(function ($claims) {
                    return is_array($claims) 
                        && isset($claims['email']) 
                        && isset($claims['name'])
                        && $claims['email'] === 'john@example.com'
                        && $claims['name'] === 'John Doe';
                })
            )
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
    public function it_throws_exception_when_user_not_found(): void
    {
        // Arrange
        $dto = new LoginUserDTO(
            email: 'nonexistent@example.com',
            password: 'SomePass@123'
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with(Mockery::on(function ($email) {
                return $email instanceof Email && $email->value() === 'nonexistent@example.com';
            }))
            ->andReturnNull();

        $this->tokenGenerator
            ->shouldNotReceive('generate');

        // Assert
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User with identifier nonexistent@example.com not found');

        // Act
        $this->useCase->execute($dto);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_password_is_incorrect(): void
    {
        // Arrange
        $dto = new LoginUserDTO(
            email: 'john@example.com',
            password: 'WrongPassword@123'
        );

        // Simulate user from database (with hashed password)
        $user = User::reconstitute(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromHash(password_hash('CorrectPassword@123', PASSWORD_BCRYPT)),
            true,
            new \DateTimeImmutable()
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with(Mockery::on(function ($email) {
                return $email instanceof Email && $email->value() === 'john@example.com';
            }))
            ->andReturn($user);

        $this->tokenGenerator
            ->shouldNotReceive('generate');

        // Assert
        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials');

        // Act
        $this->useCase->execute($dto);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_user_is_inactive(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';
        $dto = new LoginUserDTO(
            email: 'john@example.com',
            password: $plainPassword
        );

        $user = User::reconstitute(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromPlainText($plainPassword),
            false, // inactive
            new \DateTimeImmutable()
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with(Mockery::on(function ($email) {
                return $email instanceof Email && $email->value() === 'john@example.com';
            }))
            ->andReturn($user);

        $this->tokenGenerator
            ->shouldNotReceive('generate');

        // Assert
        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('User is inactive');

        // Act
        $this->useCase->execute($dto);
    }

    /**
     * @test
     */
    public function it_generates_jwt_token_for_authenticated_user(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';
        $dto = new LoginUserDTO(
            email: 'john@example.com',
            password: $plainPassword
        );

        $userId = UserId::generate();
        // Simulate user from database (with hashed password)
        $user = User::reconstitute(
            $userId,
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromHash(password_hash($plainPassword, PASSWORD_BCRYPT)),
            true,
            new \DateTimeImmutable()
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->andReturn($user);

        $tokenGeneratedForUserId = null;
        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->with(
                Mockery::on(function ($id) use (&$tokenGeneratedForUserId, $userId) {
                    $tokenGeneratedForUserId = $id;
                    return $id instanceof UserId && $id->equals($userId);
                }),
                Mockery::type('array')
            )
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
        $this->assertTrue($tokenGeneratedForUserId->equals($userId));
    }

    /**
     * @test
     */
    public function it_verifies_password_using_bcrypt(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';
        $dto = new LoginUserDTO(
            email: 'john@example.com',
            password: $plainPassword
        );

        // Simulate user from database (with hashed password)
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
        $user = User::reconstitute(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromHash($hashedPassword),
            true,
            new \DateTimeImmutable()
        );

        // Verify that the stored password is hashed
        $this->assertEquals($hashedPassword, $user->getPassword()->value());
        $this->assertStringStartsWith('$2y$', $user->getPassword()->value());

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->andReturn($user);

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->with(Mockery::type(UserId::class), Mockery::type('array'))
            ->andReturn('token');

        $this->tokenGenerator
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(3600);

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(AuthTokenDTO::class, $result);
    }

    /**
     * @test
     */
    public function it_normalizes_email_to_lowercase_before_lookup(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';
        $dto = new LoginUserDTO(
            email: 'John@Example.COM', // Mixed case
            password: $plainPassword
        );

        // Simulate user from database (with hashed password)
        $user = User::reconstitute(
            UserId::generate(),
            new UserName('John Doe'),
            new Email('john@example.com'), // Stored as lowercase
            Password::fromHash(password_hash($plainPassword, PASSWORD_BCRYPT)),
            true,
            new \DateTimeImmutable()
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with(Mockery::on(function ($email) {
                return $email instanceof Email && $email->value() === 'john@example.com';
            }))
            ->andReturn($user);

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->with(Mockery::type(UserId::class), Mockery::type('array'))
            ->andReturn('token');

        $this->tokenGenerator
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(3600);

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(AuthTokenDTO::class, $result);
    }

    /**
     * @test
     */
    public function it_returns_user_data_in_auth_token_response(): void
    {
        // Arrange
        $plainPassword = 'SecurePass@123';
        $dto = new LoginUserDTO(
            email: 'john@example.com',
            password: $plainPassword
        );

        $userId = UserId::generate();
        // Simulate user from database (with hashed password)
        $user = User::reconstitute(
            $userId,
            new UserName('John Doe'),
            new Email('john@example.com'),
            Password::fromHash(password_hash($plainPassword, PASSWORD_BCRYPT)),
            true,
            new \DateTimeImmutable()
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->andReturn($user);

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->with(Mockery::type(UserId::class), Mockery::type('array'))
            ->andReturn('token');

        $this->tokenGenerator
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(3600);

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals($userId->value(), $result->user->id);
        $this->assertEquals('John Doe', $result->user->name);
        $this->assertEquals('john@example.com', $result->user->email);
        $this->assertTrue($result->user->isActive);
    }
}

