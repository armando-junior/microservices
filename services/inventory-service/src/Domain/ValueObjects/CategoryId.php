<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidCategoryIdException;
use Ramsey\Uuid\Uuid;

/**
 * Category ID Value Object
 * 
 * Representa um identificador único de categoria (UUID v4).
 */
final class CategoryId
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria um novo CategoryId a partir de uma string UUID
     */
    public static function fromString(string $id): self
    {
        return new self($id);
    }

    /**
     * Gera um novo CategoryId (UUID v4)
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
            throw new InvalidCategoryIdException('Category ID cannot be empty');
        }

        if (!Uuid::isValid($normalized)) {
            throw new InvalidCategoryIdException("Invalid UUID format: {$this->value}");
        }
    }

    /**
     * Retorna o valor do CategoryId
     */
    public function value(): string
    {
        return strtolower($this->value);
    }

    /**
     * Compara se dois CategoryIds são iguais
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

