<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * Order Cancelled Event
 * 
 * Disparado quando um pedido é cancelado.
 */
final class OrderCancelled implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $customerId,
        private readonly string $reason,
        private readonly DateTimeImmutable $occurredOn = new DateTimeImmutable()
    ) {
    }

    public function eventName(): string
    {
        return 'sales.order.cancelled';
    }

    public function routingKey(): string
    {
        return 'sales.order.cancelled';
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'event_id' => uniqid('evt_', true),
            'occurred_at' => $this->occurredOn->format('Y-m-d\TH:i:s.uP'),
            'payload' => [
                'order_id' => $this->orderId,
                'customer_id' => $this->customerId,
                'reason' => $this->reason,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
