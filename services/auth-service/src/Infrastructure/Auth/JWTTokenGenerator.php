<?php

declare(strict_types=1);

namespace Src\Infrastructure\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Src\Application\Contracts\TokenGeneratorInterface;
use Src\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\Cache;

/**
 * JWT Token Generator
 * 
 * Implementação do gerador de tokens JWT.
 * Usa firebase/php-jwt para geração e validação de tokens.
 */
final class JWTTokenGenerator implements TokenGeneratorInterface
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 3600, // 1 hora em segundos
        private readonly string $issuer = 'auth-service'
    ) {
    }

    public function generate(UserId $userId, array $claims = []): string
    {
        $now = time();

        $payload = [
            'iss' => $this->issuer,           // Issuer
            'sub' => $userId->value(),        // Subject (user ID)
            'iat' => $now,                    // Issued at
            'exp' => $now + $this->ttl,       // Expiration
            'jti' => uniqid('jwt_', true),    // JWT ID (unique identifier)
        ];

        // Adiciona claims customizados
        foreach ($claims as $key => $value) {
            $payload[$key] = $value;
        }

        return JWT::encode($payload, $this->secret, self::ALGORITHM);
    }

    public function validate(string $token): bool
    {
        try {
            // Verifica se o token está na blacklist
            if ($this->isBlacklisted($token)) {
                return false;
            }

            JWT::decode($token, new Key($this->secret, self::ALGORITHM));
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function decode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
            
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid token: ' . $e->getMessage());
        }
    }

    public function getTTL(): int
    {
        return $this->ttl;
    }

    public function invalidate(string $token): void
    {
        try {
            $decoded = $this->decode($token);
            
            // Adiciona o token na blacklist no Redis
            // A chave expira junto com o token
            $expiresAt = $decoded['exp'] ?? time();
            $ttl = max(0, $expiresAt - time());
            
            Cache::put(
                $this->getBlacklistKey($token),
                true,
                $ttl
            );
        } catch (\Exception $e) {
            // Se não conseguir decodificar, ignora (token já é inválido)
        }
    }

    /**
     * Verifica se o token está na blacklist
     */
    private function isBlacklisted(string $token): bool
    {
        return Cache::has($this->getBlacklistKey($token));
    }

    /**
     * Gera a chave da blacklist para o token
     */
    private function getBlacklistKey(string $token): string
    {
        return 'jwt:blacklist:' . hash('sha256', $token);
    }
}

