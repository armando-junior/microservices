<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * Order Item Added Event
 * 
 * Disparado quando um item é adicionado a um pedido.
 */
final class OrderItemAdded implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $itemId,
        private readonly string $productId,
        private readonly int $quantity,
        private readonly float $unitPrice,
        private readonly DateTimeImmutable $occurredOn = new DateTimeImmutable()
    ) {
    }

    public function eventName(): string
    {
        return 'sales.order.item_added';
    }

    public function routingKey(): string
    {
        return 'sales.order.item_added';
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'event_id' => uniqid('evt_', true),
            'occurred_at' => $this->occurredOn->format('Y-m-d\TH:i:s.uP'),
            'payload' => [
                'order_id' => $this->orderId,
                'item_id' => $this->itemId,
                'product_id' => $this->productId,
                'quantity' => $this->quantity,
                'unit_price' => $this->unitPrice,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
