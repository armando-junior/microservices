<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidEmailException;

/**
 * Email Value Object
 * 
 * Representa um endereço de e-mail válido.
 */
final class Email
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de uma string
     */
    public static function fromString(string $email): self
    {
        return new self(strtolower(trim($email)));
    }

    /**
     * Valida o e-mail
     */
    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidEmailException('Email cannot be empty');
        }

        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email format: {$this->value}");
        }

        if (strlen($this->value) > 255) {
            throw new InvalidEmailException('Email must not exceed 255 characters');
        }
    }

    /**
     * Retorna o valor
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Compara com outro e-mail
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Conversão para string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
