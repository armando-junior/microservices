<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

use Src\Domain\Exceptions\InvalidPaymentTermsException;

/**
 * PaymentTerms Value Object
 * 
 * Representa o prazo de pagamento em dias.
 */
final class PaymentTerms
{
    private const MIN_DAYS = 0;
    private const MAX_DAYS = 365; // 1 ano

    private function __construct(
        private readonly int $days
    ) {
        $this->validate();
    }

    /**
     * Pagamento à vista (0 dias)
     */
    public static function immediate(): self
    {
        return new self(0);
    }

    /**
     * Pagamento a prazo (N dias)
     */
    public static function days(int $days): self
    {
        return new self($days);
    }

    /**
     * 30 dias
     */
    public static function net30(): self
    {
        return new self(30);
    }

    /**
     * 60 dias
     */
    public static function net60(): self
    {
        return new self(60);
    }

    /**
     * 90 dias
     */
    public static function net90(): self
    {
        return new self(90);
    }

    private function validate(): void
    {
        if ($this->days < self::MIN_DAYS) {
            throw new InvalidPaymentTermsException(
                sprintf('Payment terms cannot be less than %d days', self::MIN_DAYS)
            );
        }

        if ($this->days > self::MAX_DAYS) {
            throw new InvalidPaymentTermsException(
                sprintf('Payment terms cannot exceed %d days', self::MAX_DAYS)
            );
        }
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function isImmediate(): bool
    {
        return $this->days === 0;
    }

    /**
     * Calcula a data de vencimento a partir de uma data base
     */
    public function calculateDueDate(\DateTimeImmutable $baseDate): \DateTimeImmutable
    {
        return $baseDate->modify("+{$this->days} days");
    }

    public function equals(PaymentTerms $other): bool
    {
        return $this->days === $other->days;
    }

    public function __toString(): string
    {
        if ($this->days === 0) {
            return 'À vista';
        }

        return "{$this->days} dias";
    }
}


