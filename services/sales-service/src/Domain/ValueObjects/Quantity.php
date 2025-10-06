<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidQuantityException;

/**
 * Quantity Value Object
 * 
 * Representa uma quantidade (inteiro positivo).
 */
final class Quantity
{
    private function __construct(
        private readonly int $value
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de um inteiro
     */
    public static function fromInt(int $quantity): self
    {
        return new self($quantity);
    }

    /**
     * Cria zero
     */
    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Valida a quantidade
     */
    private function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidQuantityException('Quantity cannot be negative');
        }

        if ($this->value > 999999) {
            throw new InvalidQuantityException('Quantity exceeds maximum limit (999,999)');
        }
    }

    /**
     * Retorna o valor
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * Verifica se é zero
     */
    public function isZero(): bool
    {
        return $this->value === 0;
    }

    /**
     * Verifica se é positivo
     */
    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    /**
     * Adiciona outra quantidade
     */
    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    /**
     * Subtrai outra quantidade
     */
    public function subtract(self $other): self
    {
        $result = $this->value - $other->value;
        if ($result < 0) {
            throw new InvalidQuantityException('Subtraction would result in negative quantity');
        }
        return new self($result);
    }

    /**
     * Compara com outra quantidade
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Maior que
     */
    public function greaterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Menor que
     */
    public function lessThan(self $other): bool
    {
        return $this->value < $other->value;
    }

    /**
     * Conversão para string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
