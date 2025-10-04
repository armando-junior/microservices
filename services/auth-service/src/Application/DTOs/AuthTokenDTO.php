<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

/**
 * Auth Token DTO
 * 
 * Data Transfer Object para token de autenticação.
 */
final class AuthTokenDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly UserDTO $user
    ) {
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'user' => $this->user->toArray(),
        ];
    }
}

