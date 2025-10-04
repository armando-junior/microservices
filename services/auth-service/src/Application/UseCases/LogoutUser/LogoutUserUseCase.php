<?php

declare(strict_types=1);

namespace Src\Application\UseCases\LogoutUser;

use Src\Application\Contracts\TokenGeneratorInterface;

/**
 * Logout User Use Case
 * 
 * Caso de uso para logout de usuário (invalidação de token).
 */
final class LogoutUserUseCase
{
    public function __construct(
        private readonly TokenGeneratorInterface $tokenGenerator
    ) {
    }

    /**
     * Executa o caso de uso de logout
     */
    public function execute(string $token): void
    {
        // Invalida o token
        $this->tokenGenerator->invalidate($token);
    }
}

