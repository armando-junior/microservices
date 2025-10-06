<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidOrderStatusException;

/**
 * Order Status Value Object
 * 
 * Representa o status de um pedido.
 */
final class OrderStatus
{
    public const DRAFT = 'draft';
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const PROCESSING = 'processing';
    public const SHIPPED = 'shipped';
    public const DELIVERED = 'delivered';
    public const CANCELLED = 'cancelled';

    private const VALID_STATUSES = [
        self::DRAFT,
        self::PENDING,
        self::CONFIRMED,
        self::PROCESSING,
        self::SHIPPED,
        self::DELIVERED,
        self::CANCELLED,
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
     * Cria status DRAFT
     */
    public static function draft(): self
    {
        return new self(self::DRAFT);
    }

    /**
     * Cria status PENDING
     */
    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    /**
     * Cria status CONFIRMED
     */
    public static function confirmed(): self
    {
        return new self(self::CONFIRMED);
    }

    /**
     * Cria status PROCESSING
     */
    public static function processing(): self
    {
        return new self(self::PROCESSING);
    }

    /**
     * Cria status SHIPPED
     */
    public static function shipped(): self
    {
        return new self(self::SHIPPED);
    }

    /**
     * Cria status DELIVERED
     */
    public static function delivered(): self
    {
        return new self(self::DELIVERED);
    }

    /**
     * Cria status CANCELLED
     */
    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    /**
     * Valida o status
     */
    private function validate(): void
    {
        if (!in_array($this->value, self::VALID_STATUSES, true)) {
            throw new InvalidOrderStatusException(
                "Invalid order status: {$this->value}. Valid: " . implode(', ', self::VALID_STATUSES)
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
     * Verifica se é rascunho
     */
    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    /**
     * Verifica se está pendente
     */
    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    /**
     * Verifica se está confirmado
     */
    public function isConfirmed(): bool
    {
        return $this->value === self::CONFIRMED;
    }

    /**
     * Verifica se foi cancelado
     */
    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    /**
     * Verifica se foi entregue
     */
    public function isDelivered(): bool
    {
        return $this->value === self::DELIVERED;
    }

    /**
     * Verifica se pode ser cancelado
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->value, [self::DRAFT, self::PENDING, self::CONFIRMED], true);
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
