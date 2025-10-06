<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidMoneyException;

/**
 * Money Value Object
 * 
 * Representa um valor monetário em BRL com 2 casas decimais.
 */
final class Money
{
    private function __construct(
        private readonly float $value
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de um float
     */
    public static function fromFloat(float $amount): self
    {
        return new self(round($amount, 2));
    }

    /**
     * Cria zero
     */
    public static function zero(): self
    {
        return new self(0.0);
    }

    /**
     * Valida o valor
     */
    private function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidMoneyException('Money value cannot be negative');
        }

        // Limitar a valores razoáveis (999 milhões)
        if ($this->value > 999999999.99) {
            throw new InvalidMoneyException('Money value exceeds maximum limit (999,999,999.99)');
        }
    }

    /**
     * Retorna o valor
     */
    public function value(): float
    {
        return $this->value;
    }

    /**
     * Retorna formatado (R$ 1.234,56)
     */
    public function formatted(): string
    {
        return 'R$ ' . number_format($this->value, 2, ',', '.');
    }

    /**
     * Verifica se é zero
     */
    public function isZero(): bool
    {
        return abs($this->value) < 0.01; // Comparação de floats
    }

    /**
     * Verifica se é positivo
     */
    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    /**
     * Adiciona outro valor
     */
    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    /**
     * Subtrai outro valor
     */
    public function subtract(self $other): self
    {
        $result = $this->value - $other->value;
        if ($result < 0) {
            throw new InvalidMoneyException('Subtraction would result in negative value');
        }
        return new self($result);
    }

    /**
     * Multiplica por um fator
     */
    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new InvalidMoneyException('Multiplication factor cannot be negative');
        }
        return new self($this->value * $factor);
    }

    /**
     * Compara com outro valor
     */
    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.01;
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
        return number_format($this->value, 2, '.', '');
    }
}
