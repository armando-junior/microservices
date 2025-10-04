<?php

declare(strict_types=1);

namespace Src\Application\Contracts;

use Src\Domain\ValueObjects\UserId;

/**
 * Token Generator Interface
 * 
 * Interface para geração de tokens JWT.
 * Implementação deve estar na camada de Infrastructure.
 */
interface TokenGeneratorInterface
{
    /**
     * Gera um access token para o usuário
     */
    public function generate(UserId $userId, array $claims = []): string;

    /**
     * Valida um token
     */
    public function validate(string $token): bool;

    /**
     * Decodifica um token e retorna os claims
     */
    public function decode(string $token): array;

    /**
     * Obtém o tempo de expiração do token (em segundos)
     */
    public function getTTL(): int;

    /**
     * Invalida um token (logout)
     */
    public function invalidate(string $token): void;
}

