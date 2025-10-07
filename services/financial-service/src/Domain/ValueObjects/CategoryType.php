<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidCategoryTypeException;

/**
 * CategoryType Value Object
 * 
 * Representa o tipo de categoria financeira.
 * 
 * Tipos possíveis:
 * - income: Receita (ex: vendas, serviços)
 * - expense: Despesa (ex: fornecedores, salários)
 */
final class CategoryType
{
    private const INCOME = 'income';
    private const EXPENSE = 'expense';

    private const VALID_TYPES = [
        self::INCOME,
        self::EXPENSE,
    ];

    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    public static function income(): self
    {
        return new self(self::INCOME);
    }

    public static function expense(): self
    {
        return new self(self::EXPENSE);
    }

    public static function fromString(string $type): self
    {
        return new self($type);
    }

    private function validate(): void
    {
        if (!in_array($this->value, self::VALID_TYPES, true)) {
            throw new InvalidCategoryTypeException(
                "Invalid category type: {$this->value}. " .
                "Valid types are: " . implode(', ', self::VALID_TYPES)
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isIncome(): bool
    {
        return $this->value === self::INCOME;
    }

    public function isExpense(): bool
    {
        return $this->value === self::EXPENSE;
    }

    public function equals(CategoryType $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return match($this->value) {
            self::INCOME => 'Receita',
            self::EXPENSE => 'Despesa',
        };
    }
}


