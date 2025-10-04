<?php

declare(strict_types=1);

namespace Src\Application\UseCases\RegisterUser;

use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\RegisterUserDTO;
use Src\Application\DTOs\UserDTO;
use Src\Application\Exceptions\EmailAlreadyExistsException;
use Src\Domain\Entities\User;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Password;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

/**
 * Register User Use Case
 * 
 * Caso de uso para registro de novo usuário no sistema.
 */
final class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Executa o caso de uso de registro de usuário
     * 
     * @throws EmailAlreadyExistsException
     */
    public function execute(RegisterUserDTO $dto): UserDTO
    {
        // 1. Criar Value Objects
        $email = new Email($dto->email);
        $name = new UserName($dto->name);
        $password = Password::fromPlainText($dto->password);

        // 2. Verificar se email já existe
        if ($this->userRepository->existsByEmail($email)) {
            throw new EmailAlreadyExistsException($dto->email);
        }

        // 3. Criar entidade User
        $user = User::create(
            UserId::generate(),
            $name,
            $email,
            $password
        );

        // 4. Persistir usuário
        $this->userRepository->save($user);

        // 5. Publicar eventos de domínio
        $events = $user->pullDomainEvents();
        foreach ($events as $event) {
            $this->eventPublisher->publish($event);
        }

        // 6. Retornar DTO do usuário criado
        return UserDTO::fromEntity($user);
    }
}

