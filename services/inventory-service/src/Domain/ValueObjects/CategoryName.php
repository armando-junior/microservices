<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidCategoryNameException;

/**
 * Category Name Value Object
 * 
 * Representa o nome de uma categoria.
 * 
 * Regras:
 * - 3 a 100 caracteres
 * - Não pode conter apenas espaços
 */
final class CategoryName
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 100;

    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    private function validate(): void
    {
        $trimmed = trim($this->value);

        if (empty($trimmed)) {
            throw new InvalidCategoryNameException('Category name cannot be empty');
        }

        $length = mb_strlen($trimmed);
        
        if ($length < self::MIN_LENGTH) {
            throw new InvalidCategoryNameException(
                "Category name must be at least " . self::MIN_LENGTH . " characters long"
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidCategoryNameException(
                "Category name must not exceed " . self::MAX_LENGTH . " characters"
            );
        }
    }

    public function value(): string
    {
        return trim($this->value);
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

