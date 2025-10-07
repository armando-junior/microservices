<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * AccountReceivableReceived Domain Event
 * 
 * Evento disparado quando uma conta a receber é recebida.
 */
final class AccountReceivableReceived
{
    public function __construct(
        public readonly string $accountReceivableId,
        public readonly string $customerId,
        public readonly float $amount,
        public readonly DateTimeImmutable $receivedAt,
        public readonly DateTimeImmutable $occurredOn
    ) {
    }

    public function toArray(): array
    {
        return [
            'account_receivable_id' => $this->accountReceivableId,
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
            'received_at' => $this->receivedAt->format('Y-m-d H:i:s'),
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}


