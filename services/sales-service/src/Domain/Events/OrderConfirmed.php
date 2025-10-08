<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * Order Confirmed Event
 * 
 * Disparado quando um pedido é confirmado.
 */
final class OrderConfirmed implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $customerId,
        private readonly float $totalAmount,
        private readonly int $itemCount,
        private readonly DateTimeImmutable $occurredOn = new DateTimeImmutable()
    ) {
    }

    public function eventName(): string
    {
        return 'sales.order.confirmed';
    }

    public function routingKey(): string
    {
        return 'sales.order.confirmed';
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

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
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
                'total_amount' => $this->totalAmount,
                'item_count' => $this->itemCount,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
