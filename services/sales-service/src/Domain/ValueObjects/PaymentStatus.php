<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidPaymentStatusException;

/**
 * Payment Status Value Object
 * 
 * Representa o status de pagamento de um pedido.
 */
final class PaymentStatus
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const REFUNDED = 'refunded';
    public const FAILED = 'failed';

    private const VALID_STATUSES = [
        self::PENDING,
        self::PAID,
        self::REFUNDED,
        self::FAILED,
    ];

    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    /**
     * Cria a partir de uma string
     */
    public static function fromString(string $status): self
    {
        return new self(strtolower(trim($status)));
    }

    /**
     * Cria status PENDING
     */
    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    /**
     * Cria status PAID
     */
    public static function paid(): self
    {
        return new self(self::PAID);
    }

    /**
     * Cria status REFUNDED
     */
    public static function refunded(): self
    {
        return new self(self::REFUNDED);
    }

    /**
     * Cria status FAILED
     */
    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    /**
     * Valida o status
     */
    private function validate(): void
    {
        if (!in_array($this->value, self::VALID_STATUSES, true)) {
            throw new InvalidPaymentStatusException(
                "Invalid payment status: {$this->value}. Valid: " . implode(', ', self::VALID_STATUSES)
            );
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
     * Verifica se está pendente
     */
    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    /**
     * Verifica se foi pago
     */
    public function isPaid(): bool
    {
        return $this->value === self::PAID;
    }

    /**
     * Verifica se foi reembolsado
     */
    public function isRefunded(): bool
    {
        return $this->value === self::REFUNDED;
    }

    /**
     * Verifica se falhou
     */
    public function isFailed(): bool
    {
        return $this->value === self::FAILED;
    }

    /**
     * Compara com outro status
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
