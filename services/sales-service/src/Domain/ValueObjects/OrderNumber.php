<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidOrderNumberException;

/**
 * Order Number Value Object
 * 
 * Representa o número sequencial único de um pedido (ORD-2024-0001).
 */
final class OrderNumber
{
    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de uma string
     */
    public static function fromString(string $orderNumber): self
    {
        return new self(strtoupper(trim($orderNumber)));
    }

    /**
     * Gera um novo número de pedido
     * Formato: ORD-{YEAR}-{SEQUENCE}
     */
    public static function generate(int $sequence): self
    {
        $year = date('Y');
        $orderNumber = sprintf('ORD-%s-%04d', $year, $sequence);
        return new self($orderNumber);
    }

    /**
     * Valida o número do pedido
     */
    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidOrderNumberException('Order number cannot be empty');
        }

        // Formato: ORD-YYYY-NNNN
        if (!preg_match('/^ORD-\d{4}-\d{4,}$/', $this->value)) {
            throw new InvalidOrderNumberException('Invalid order number format. Expected: ORD-YYYY-NNNN');
        }
    }

    /**
     * Retorna o valor
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Compara com outro número de pedido
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Conversão para string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
