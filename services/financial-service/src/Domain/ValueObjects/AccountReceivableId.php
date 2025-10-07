<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;
use Src\Domain\Exceptions\InvalidAccountReceivableIdException;

/**
 * AccountReceivableId Value Object
 * 
 * Representa um identificador único de conta a receber (UUID v4).
 */
final class AccountReceivableId
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
            throw new InvalidAccountReceivableIdException("Invalid account receivable ID: {$this->value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(AccountReceivableId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}


