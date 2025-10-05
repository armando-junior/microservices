<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidQuantityException;

/**
 * Quantity Value Object
 * 
 * Representa a quantidade de um produto em estoque.
 * 
 * Regras:
 * - Deve ser >= 0
 * - Inteiro (sem decimais)
 */
final class Quantity
{
    private function __construct(
        private readonly int $value
    ) {
        $this->validate();
    }

    /**
     * Cria um novo Quantity a partir de um inteiro
     */
    public static function fromInt(int $quantity): self
    {
        return new self($quantity);
    }

    /**
     * Cria uma quantidade zero
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
    }

    /**
     * Retorna o valor da quantidade
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * Adiciona uma quantidade
     */
    public function add(self $other): self
    {
        return new self($this->value + $other->value());
    }

    /**
     * Subtrai uma quantidade
     */
    public function subtract(self $other): self
    {
        $result = $this->value - $other->value();
        
        if ($result < 0) {
            throw new InvalidQuantityException('Result quantity cannot be negative');
        }
        
        return new self($result);
    }

    /**
     * Compara se duas quantidades são iguais
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    /**
     * Verifica se a quantidade é maior que outra
     */
    public function greaterThan(self $other): bool
    {
        return $this->value > $other->value();
    }

    /**
     * Verifica se a quantidade é menor que outra
     */
    public function lessThan(self $other): bool
    {
        return $this->value < $other->value();
    }

    /**
     * Verifica se a quantidade é menor ou igual a outra
     */
    public function lessThanOrEqual(self $other): bool
    {
        return $this->value <= $other->value();
    }

    /**
     * Verifica se a quantidade é zero
     */
    public function isZero(): bool
    {
        return $this->value === 0;
    }

    /**
     * Verifica se há quantidade suficiente
     */
    public function isSufficient(self $required): bool
    {
        return $this->value >= $required->value();
    }

    /**
     * Converte para string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}

