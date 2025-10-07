<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidReceivableStatusException;

/**
 * ReceivableStatus Value Object
 * 
 * Representa o status de recebimento de uma conta a receber.
 * 
 * Estados possíveis:
 * - pending: Aguardando recebimento
 * - received: Recebido
 * - overdue: Vencido (não recebido até a data de vencimento)
 * - cancelled: Cancelado
 */
final class ReceivableStatus
{
    private const PENDING = 'pending';
    private const RECEIVED = 'received';
    private const OVERDUE = 'overdue';
    private const CANCELLED = 'cancelled';

    private const VALID_STATUSES = [
        self::PENDING,
        self::RECEIVED,
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

    public static function received(): self
    {
        return new self(self::RECEIVED);
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
            throw new InvalidReceivableStatusException(
                "Invalid receivable status: {$this->value}. " .
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

    public function isReceived(): bool
    {
        return $this->value === self::RECEIVED;
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
     * Verifica se o status permite recebimento
     */
    public function canReceive(): bool
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

    public function equals(ReceivableStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return match($this->value) {
            self::PENDING => 'Pendente',
            self::RECEIVED => 'Recebido',
            self::OVERDUE => 'Vencido',
            self::CANCELLED => 'Cancelado',
        };
    }
}


