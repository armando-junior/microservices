<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

/**
 * Login User DTO
 * 
 * Data Transfer Object para login de usuário.
 */
final class LoginUserDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {
    }

    /**
     * Cria DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password']
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}

