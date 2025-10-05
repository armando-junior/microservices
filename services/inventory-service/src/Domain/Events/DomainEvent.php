<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * Base Domain Event
 * 
 * Classe base para todos os eventos de domínio.
 */
abstract class DomainEvent
{
    public function __construct(
        private readonly array $payload,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    abstract public function eventName(): string;

    public function payload(): array
    {
        return $this->payload;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'event' => $this->eventName(),
            'payload' => $this->payload(),
            'occurred_at' => $this->occurredAt()->format('Y-m-d\TH:i:s.u\Z'),
        ];
    }
}

