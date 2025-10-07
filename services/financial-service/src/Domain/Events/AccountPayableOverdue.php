<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * AccountPayableOverdue Domain Event
 * 
 * Evento disparado quando uma conta a pagar fica vencida.
 */
final class AccountPayableOverdue
{
    public function __construct(
        public readonly string $accountPayableId,
        public readonly string $supplierId,
        public readonly float $amount,
        public readonly DateTimeImmutable $dueDate,
        public readonly DateTimeImmutable $occurredOn
    ) {
    }

    public function toArray(): array
    {
        return [
            'account_payable_id' => $this->accountPayableId,
            'supplier_id' => $this->supplierId,
            'amount' => $this->amount,
            'due_date' => $this->dueDate->format('Y-m-d'),
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}


