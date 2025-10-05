<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidPriceException;

/**
 * Price Value Object
 * 
 * Representa o preço de um produto.
 * 
 * Regras:
 * - Deve ser >= 0
 * - Armazenado com 2 casas decimais
 */
final class Price
{
    private function __construct(
        private readonly float $value
    ) {
        $this->validate();
    }

    /**
     * Cria um novo Price a partir de um float
     */
    public static function fromFloat(float $price): self
    {
        return new self($price);
    }

    /**
     * Cria um novo Price a partir de uma string
     */
    public static function fromString(string $price): self
    {
        $cleaned = str_replace(',', '.', $price);
        return new self((float) $cleaned);
    }

    /**
     * Valida o preço
     */
    private function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidPriceException('Price cannot be negative');
        }
    }

    /**
     * Retorna o valor do preço (2 casas decimais)
     */
    public function value(): float
    {
        return round($this->value, 2);
    }

    /**
     * Retorna o preço formatado como string
     */
    public function formatted(): string
    {
        return number_format($this->value(), 2, '.', '');
    }

    /**
     * Compara se dois preços são iguais
     */
    public function equals(self $other): bool
    {
        return abs($this->value() - $other->value()) < 0.01;
    }

    /**
     * Verifica se o preço é maior que outro
     */
    public function greaterThan(self $other): bool
    {
        return $this->value() > $other->value();
    }

    /**
     * Verifica se o preço é menor que outro
     */
    public function lessThan(self $other): bool
    {
        return $this->value() < $other->value();
    }

    /**
     * Verifica se o preço é zero
     */
    public function isZero(): bool
    {
        return $this->value() === 0.0;
    }

    /**
     * Converte para string
     */
    public function __toString(): string
    {
        return $this->formatted();
    }
}

