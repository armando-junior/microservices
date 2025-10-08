<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * Stock Low Alert Event
 * 
 * Disparado quando o estoque de um produto atinge um nível baixo.
 */
final class StockLowAlert implements DomainEvent
{
    public function __construct(
        private readonly string $productId,
        private readonly string $productName,
        private readonly int $currentStock,
        private readonly int $minimumStock,
        private readonly DateTimeImmutable $occurredOn = new DateTimeImmutable()
    ) {
    }

    public function eventName(): string
    {
        return 'inventory.stock.low';
    }

    public function routingKey(): string
    {
        return 'inventory.stock.low';
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getCurrentStock(): int
    {
        return $this->currentStock;
    }

    public function getMinimumStock(): int
    {
        return $this->minimumStock;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'event_id' => uniqid('evt_', true),
            'occurred_at' => $this->occurredOn->format('Y-m-d\TH:i:s.uP'),
            'payload' => [
                'product_id' => $this->productId,
                'product_name' => $this->productName,
                'current_stock' => $this->currentStock,
                'minimum_stock' => $this->minimumStock,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

