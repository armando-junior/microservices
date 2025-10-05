<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidStockIdException;
use Ramsey\Uuid\Uuid;

/**
 * Stock ID Value Object
 */
final class StockId
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    private function validate(): void
    {
        $normalized = strtolower(trim($this->value));

        if (empty($normalized)) {
            throw new InvalidStockIdException('Stock ID cannot be empty');
        }

        if (!Uuid::isValid($normalized)) {
            throw new InvalidStockIdException("Invalid UUID format: {$this->value}");
        }
    }

    public function value(): string
    {
        return strtolower($this->value);
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return $this->value();
    }
}

