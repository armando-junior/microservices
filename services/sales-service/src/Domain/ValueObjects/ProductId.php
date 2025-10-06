<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;
use Src\Domain\Exceptions\InvalidProductIdException;

/**
 * Product ID Value Object
 * 
 * Representa o ID de um produto (referência ao Inventory Service).
 */
final readonly class ProductId
{
    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ProductId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (!Uuid::isValid($this->value)) {
            throw InvalidProductIdException::withValue($this->value);
        }
    }
}
