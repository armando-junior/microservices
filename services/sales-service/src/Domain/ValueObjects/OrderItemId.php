<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;
use Src\Domain\Exceptions\InvalidOrderItemIdException;

/**
 * Order Item ID Value Object
 * 
 * Representa o identificador único de um item de pedido (UUID).
 */
final class OrderItemId
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria um novo Order Item ID
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
            throw InvalidOrderItemIdException::withValue($this->value);
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
     * Compara com outro Order Item ID
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
