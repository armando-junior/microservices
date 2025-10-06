<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;
use Src\Domain\Exceptions\InvalidCustomerIdException;

/**
 * Customer ID Value Object
 * 
 * Representa o identificador único de um cliente (UUID).
 */
final class CustomerId
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria um novo Customer ID
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    /**
     * Cria a partir de uma string
     */
    public static function fromString(string $id): self
    {
        return new self($id);
    }

    /**
     * Valida o UUID
     */
    private function validate(): void
    {
        if (!Uuid::isValid($this->value)) {
            throw InvalidCustomerIdException::withValue($this->value);
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
     * Compara com outro Customer ID
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
