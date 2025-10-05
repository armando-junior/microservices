<?php

declare(strict_types=1);

namespace Src\Application\UseCases\LoginUser;

use Src\Application\Contracts\TokenGeneratorInterface;
use Src\Application\DTOs\AuthTokenDTO;
use Src\Application\DTOs\LoginUserDTO;
use Src\Application\DTOs\UserDTO;
use Src\Application\Exceptions\InvalidCredentialsException;
use Src\Application\Exceptions\UserNotFoundException;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;

/**
 * Login User Use Case
 * 
 * Caso de uso para autenticação de usuário.
 */
final class LoginUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenGeneratorInterface $tokenGenerator
    ) {
    }

    /**
     * Executa o caso de uso de login
     * 
     * @throws InvalidCredentialsException
     * @throws UserNotFoundException
     */
    public function execute(LoginUserDTO $dto): AuthTokenDTO
    {
        // 1. Criar Value Objects
        $email = new Email($dto->email);
        $password = Password::fromPlainText($dto->password);

        // 2. Buscar usuário por email
        $user = $this->userRepository->findByEmail($email);
        
        if ($user === null) {
            throw new UserNotFoundException($dto->email);
        }

        // 3. Verificar se usuário está ativo
        if (!$user->isActive()) {
            throw new InvalidCredentialsException('User is inactive');
        }

        // 4. Verificar senha
        if (!$user->verifyPassword($password)) {
            throw new InvalidCredentialsException();
        }

        // 5. Gerar token JWT
        $token = $this->tokenGenerator->generate(
            $user->getId(),
            [
                'email' => $user->getEmail()->value(),
                'name' => $user->getName()->value(),
            ]
        );

        // 6. Retornar token e dados do usuário
        return new AuthTokenDTO(
            accessToken: $token,
            tokenType: 'bearer',
            expiresIn: $this->tokenGenerator->getTTL(),
            user: UserDTO::fromEntity($user)
        );
    }
}

