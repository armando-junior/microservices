<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

use Src\Domain\Entities\User;

/**
 * User DTO
 * 
 * Data Transfer Object para representação de usuário.
 */
final class UserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly bool $isActive,
        public readonly ?string $emailVerifiedAt,
        public readonly string $createdAt,
        public readonly ?string $updatedAt
    ) {
    }

    /**
     * Cria DTO a partir de entidade User
     */
    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId()->value(),
            name: $user->getName()->value(),
            email: $user->getEmail()->value(),
            isActive: $user->isActive(),
            emailVerifiedAt: $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
            createdAt: $user->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $user->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->isActive,
            'email_verified_at' => $this->emailVerifiedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

