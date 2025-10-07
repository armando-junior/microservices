<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidMoneyException;

/**
 * Money Value Object
 * 
 * Representa um valor monetário com precisão decimal (2 casas).
 * Imutável e sempre positivo ou zero.
 */
final class Money
{
    private const DECIMAL_PLACES = 2;
    private const SCALE = 100; // Para trabalhar com inteiros (centavos)

    private function __construct(
        private readonly int $amountInCents
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de um valor decimal (ex: 150.50)
     */
    public static function fromFloat(float $amount): self
    {
        if ($amount < 0) {
            throw new InvalidMoneyException('Amount cannot be negative');
        }

        // Converte para centavos para evitar problemas de precisão de float
        $cents = (int) round($amount * self::SCALE);
        
        return new self($cents);
    }

    /**
     * Cria a partir de centavos (ex: 15050)
     */
    public static function fromCents(int $cents): self
    {
        if ($cents < 0) {
            throw new InvalidMoneyException('Amount cannot be negative');
        }

        return new self($cents);
    }

    /**
     * Cria zero
     */
    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Valida o valor
     */
    private function validate(): void
    {
        if ($this->amountInCents < 0) {
            throw new InvalidMoneyException('Amount cannot be negative');
        }
    }

    /**
     * Retorna o valor em centavos
     */
    public function cents(): int
    {
        return $this->amountInCents;
    }

    /**
     * Retorna o valor como float (ex: 150.50)
     */
    public function toFloat(): float
    {
        return $this->amountInCents / self::SCALE;
    }

    /**
     * Retorna o valor formatado (ex: "150.50")
     */
    public function toString(): string
    {
        return number_format($this->toFloat(), self::DECIMAL_PLACES, '.', '');
    }

    /**
     * Retorna o valor formatado em BRL (ex: "R$ 150,50")
     */
    public function toBRL(): string
    {
        return 'R$ ' . number_format($this->toFloat(), self::DECIMAL_PLACES, ',', '.');
    }

    /**
     * Soma dois valores
     */
    public function add(Money $other): self
    {
        return new self($this->amountInCents + $other->amountInCents);
    }

    /**
     * Subtrai dois valores
     */
    public function subtract(Money $other): self
    {
        $result = $this->amountInCents - $other->amountInCents;
        
        if ($result < 0) {
            throw new InvalidMoneyException('Subtraction would result in negative amount');
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

        $result = (int) round($this->amountInCents * $factor);
        
        return new self($result);
    }

    /**
     * Calcula porcentagem (ex: 10% de R$ 100 = R$ 10)
     */
    public function percentage(float $percent): self
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidMoneyException('Percentage must be between 0 and 100');
        }

        return $this->multiply($percent / 100);
    }

    /**
     * Verifica se é zero
     */
    public function isZero(): bool
    {
        return $this->amountInCents === 0;
    }

    /**
     * Verifica se é maior que outro valor
     */
    public function greaterThan(Money $other): bool
    {
        return $this->amountInCents > $other->amountInCents;
    }

    /**
     * Verifica se é maior ou igual a outro valor
     */
    public function greaterThanOrEqual(Money $other): bool
    {
        return $this->amountInCents >= $other->amountInCents;
    }

    /**
     * Verifica se é menor que outro valor
     */
    public function lessThan(Money $other): bool
    {
        return $this->amountInCents < $other->amountInCents;
    }

    /**
     * Verifica se é menor ou igual a outro valor
     */
    public function lessThanOrEqual(Money $other): bool
    {
        return $this->amountInCents <= $other->amountInCents;
    }

    /**
     * Verifica igualdade
     */
    public function equals(Money $other): bool
    {
        return $this->amountInCents === $other->amountInCents;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}


