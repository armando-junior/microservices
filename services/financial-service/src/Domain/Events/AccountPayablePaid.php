<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * AccountPayablePaid Domain Event
 * 
 * Evento disparado quando uma conta a pagar é paga.
 */
final class AccountPayablePaid
{
    public function __construct(
        public readonly string $accountPayableId,
        public readonly string $supplierId,
        public readonly float $amount,
        public readonly DateTimeImmutable $paidAt,
        public readonly DateTimeImmutable $occurredOn
    ) {
    }

    public function toArray(): array
    {
        return [
            'account_payable_id' => $this->accountPayableId,
            'supplier_id' => $this->supplierId,
            'amount' => $this->amount,
            'paid_at' => $this->paidAt->format('Y-m-d H:i:s'),
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}


