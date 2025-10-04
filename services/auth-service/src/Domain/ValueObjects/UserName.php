<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidUserNameException;

/**
 * UserName Value Object
 * 
 * Representa o nome de um usuário.
 */
final class UserName
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 100;

    private string $value;

    public function __construct(string $name)
    {
        $this->validate($name);
        $this->value = trim($name);
    }

    /**
     * Valida o nome do usuário
     */
    private function validate(string $name): void
    {
        $trimmed = trim($name);

        if (empty($trimmed)) {
            throw new InvalidUserNameException('User name cannot be empty');
        }

        if (strlen($trimmed) < self::MIN_LENGTH) {
            throw new InvalidUserNameException(
                sprintf('User name must be at least %d characters long', self::MIN_LENGTH)
            );
        }

        if (strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidUserNameException(
                sprintf('User name cannot exceed %d characters', self::MAX_LENGTH)
            );
        }

        // Validar caracteres permitidos (letras, espaços, hífens e apóstrofos)
        if (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $trimmed)) {
            throw new InvalidUserNameException(
                'User name can only contain letters, spaces, hyphens and apostrophes'
            );
        }
    }

    /**
     * Retorna o valor do nome
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Retorna o primeiro nome
     */
    public function firstName(): string
    {
        $parts = explode(' ', $this->value);
        return $parts[0];
    }

    /**
     * Retorna o último nome
     */
    public function lastName(): string
    {
        $parts = explode(' ', $this->value);
        return count($parts) > 1 ? end($parts) : '';
    }

    /**
     * Retorna as iniciais
     */
    public function initials(): string
    {
        $parts = explode(' ', $this->value);
        $initials = '';
        
        foreach ($parts as $part) {
            if (!empty($part)) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }
        
        return $initials;
    }

    /**
     * Verifica se é igual a outro UserName
     */
    public function equals(UserName $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Representação em string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Serialização para JSON
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}

