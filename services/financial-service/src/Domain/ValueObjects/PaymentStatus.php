<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidPaymentStatusException;

/**
 * PaymentStatus Value Object
 * 
 * Representa o status de pagamento de uma conta a pagar.
 * 
 * Estados possíveis:
 * - pending: Aguardando pagamento
 * - paid: Pago
 * - overdue: Vencido (não pago até a data de vencimento)
 * - cancelled: Cancelado
 */
final class PaymentStatus
{
    private const PENDING = 'pending';
    private const PAID = 'paid';
    private const OVERDUE = 'overdue';
    private const CANCELLED = 'cancelled';

    private const VALID_STATUSES = [
        self::PENDING,
        self::PAID,
        self::OVERDUE,
        self::CANCELLED,
    ];

    private function __construct(
        private readonly string $value
    ) {
        $this->validate();
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function paid(): self
    {
        return new self(self::PAID);
    }

    public static function overdue(): self
    {
        return new self(self::OVERDUE);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function fromString(string $status): self
    {
        return new self($status);
    }

    private function validate(): void
    {
        if (!in_array($this->value, self::VALID_STATUSES, true)) {
            throw new InvalidPaymentStatusException(
                "Invalid payment status: {$this->value}. " .
                "Valid statuses are: " . implode(', ', self::VALID_STATUSES)
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->value === self::PAID;
    }

    public function isOverdue(): bool
    {
        return $this->value === self::OVERDUE;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    /**
     * Verifica se o status permite pagamento
     */
    public function canPay(): bool
    {
        return in_array($this->value, [self::PENDING, self::OVERDUE], true);
    }

    /**
     * Verifica se o status permite cancelamento
     */
    public function canCancel(): bool
    {
        return in_array($this->value, [self::PENDING, self::OVERDUE], true);
    }

    public function equals(PaymentStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return match($this->value) {
            self::PENDING => 'Pendente',
            self::PAID => 'Pago',
            self::OVERDUE => 'Vencido',
            self::CANCELLED => 'Cancelado',
        };
    }
}


