<?php

declare(strict_types=1);

namespace Src\Application\UseCases\GetUser;

use Src\Application\DTOs\UserDTO;
use Src\Application\Exceptions\UserNotFoundException;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\UserId;

/**
 * Get User By Id Use Case
 * 
 * Caso de uso para buscar usuário por ID.
 */
final class GetUserByIdUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Executa o caso de uso
     * 
     * @throws UserNotFoundException
     */
    public function execute(string $userId): UserDTO
    {
        $id = UserId::fromString($userId);
        
        $user = $this->userRepository->findById($id);
        
        if ($user === null) {
            throw new UserNotFoundException($userId);
        }

        return UserDTO::fromEntity($user);
    }
}

