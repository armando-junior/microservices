<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidSKUException;

/**
 * SKU (Stock Keeping Unit) Value Object
 * 
 * Representa um código único de produto.
 * 
 * Regras:
 * - 3 a 50 caracteres
 * - Apenas letras, números, hífens e underscores
 * - Case insensitive (armazenado em uppercase)
 */
final class SKU
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 50;
    private const PATTERN = '/^[A-Z0-9\-_]+$/';

    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria um novo SKU a partir de uma string
     */
    public static function fromString(string $sku): self
    {
        return new self($sku);
    }

    /**
     * Valida o SKU
     */
    private function validate(): void
    {
        $normalized = strtoupper(trim($this->value));

        if (empty($normalized)) {
            throw new InvalidSKUException('SKU cannot be empty');
        }

        $length = strlen($normalized);
        if ($length < self::MIN_LENGTH) {
            throw new InvalidSKUException(
                "SKU must be at least " . self::MIN_LENGTH . " characters long"
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidSKUException(
                "SKU must not exceed " . self::MAX_LENGTH . " characters"
            );
        }

        if (!preg_match(self::PATTERN, $normalized)) {
            throw new InvalidSKUException(
                'SKU can only contain letters, numbers, hyphens, and underscores'
            );
        }
    }

    /**
     * Retorna o valor do SKU (sempre em uppercase)
     */
    public function value(): string
    {
        return strtoupper($this->value);
    }

    /**
     * Compara se dois SKUs são iguais (case insensitive)
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

