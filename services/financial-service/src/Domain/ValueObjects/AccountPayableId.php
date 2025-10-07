<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;
use Src\Domain\Exceptions\InvalidAccountPayableIdException;

/**
 * AccountPayableId Value Object
 * 
 * Representa um identificador único de conta a pagar (UUID v4).
 */
final class AccountPayableId
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    private function validate(): void
    {
        if (!Uuid::isValid($this->value)) {
            throw new InvalidAccountPayableIdException("Invalid account payable ID: {$this->value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(AccountPayableId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}


