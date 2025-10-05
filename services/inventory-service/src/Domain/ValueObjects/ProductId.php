<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidProductIdException;
use Ramsey\Uuid\Uuid;

/**
 * Product ID Value Object
 * 
 * Representa um identificador único de produto (UUID v4).
 */
final class ProductId
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria um novo ProductId a partir de uma string UUID
     */
    public static function fromString(string $id): self
    {
        return new self($id);
    }

    /**
     * Gera um novo ProductId (UUID v4)
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    /**
     * Valida o UUID
     */
    private function validate(): void
    {
        $normalized = strtolower(trim($this->value));

        if (empty($normalized)) {
            throw new InvalidProductIdException('Product ID cannot be empty');
        }

        if (!Uuid::isValid($normalized)) {
            throw new InvalidProductIdException("Invalid UUID format: {$this->value}");
        }
    }

    /**
     * Retorna o valor do ProductId
     */
    public function value(): string
    {
        return strtolower($this->value);
    }

    /**
     * Compara se dois ProductIds são iguais
     */
    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    /**
     * Converte para string
     */
    public function __toString(): string
    {
        return $this->value();
    }
}

