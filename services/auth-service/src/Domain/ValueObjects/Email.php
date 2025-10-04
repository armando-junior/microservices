<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidEmailException;

/**
 * Email Value Object
 * 
 * Representa um endereço de email válido no domínio.
 * Value Objects são imutáveis e garantem invariantes do domínio.
 */
final class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $this->validate($email);
        $this->value = strtolower(trim($email));
    }

    /**
     * Factory method para criar a partir de string
     */
    public static function fromString(string $email): self
    {
        return new self($email);
    }

    /**
     * Valida se o email é válido
     */
    private function validate(string $email): void
    {
        $trimmed = trim($email);
        
        if (empty($trimmed)) {
            throw new InvalidEmailException('Email cannot be empty');
        }

        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email format: {$trimmed}");
        }

        // Validações adicionais
        [$local, $domain] = explode('@', $trimmed);

        if (strlen($local) > 64) {
            throw new InvalidEmailException('Email local part cannot exceed 64 characters');
        }

        if (strlen($domain) > 255) {
            throw new InvalidEmailException('Email domain cannot exceed 255 characters');
        }
    }

    /**
     * Retorna o valor do email
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Retorna a parte local do email (antes do @)
     */
    public function localPart(): string
    {
        return explode('@', $this->value)[0];
    }

    /**
     * Retorna o domínio do email (depois do @)
     */
    public function domain(): string
    {
        return explode('@', $this->value)[1];
    }

    /**
     * Verifica se é igual a outro email
     */
    public function equals(Email $other): bool
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

