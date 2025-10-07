<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidSupplierNameException;

/**
 * SupplierName Value Object
 * 
 * Representa o nome de um fornecedor com validação.
 */
final class SupplierName
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 150;

    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $name): self
    {
        return new self(trim($name));
    }

    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidSupplierNameException('Supplier name cannot be empty');
        }

        $length = mb_strlen($this->value);

        if ($length < self::MIN_LENGTH) {
            throw new InvalidSupplierNameException(
                sprintf('Supplier name must have at least %d characters', self::MIN_LENGTH)
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidSupplierNameException(
                sprintf('Supplier name cannot exceed %d characters', self::MAX_LENGTH)
            );
        }

        // Verifica se contém apenas caracteres válidos (letras, números, espaços e pontuação comum)
        if (!preg_match('/^[\p{L}\p{N}\s\.\,\-\&\'\"]+$/u', $this->value)) {
            throw new InvalidSupplierNameException('Supplier name contains invalid characters');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(SupplierName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}


