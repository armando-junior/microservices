<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * AccountReceivableOverdue Domain Event
 * 
 * Evento disparado quando uma conta a receber fica vencida.
 */
final class AccountReceivableOverdue
{
    public function __construct(
        public readonly string $accountReceivableId,
        public readonly string $customerId,
        public readonly float $amount,
        public readonly DateTimeImmutable $dueDate,
        public readonly DateTimeImmutable $occurredOn
    ) {
    }

    public function toArray(): array
    {
        return [
            'account_receivable_id' => $this->accountReceivableId,
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
            'due_date' => $this->dueDate->format('Y-m-d'),
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}


