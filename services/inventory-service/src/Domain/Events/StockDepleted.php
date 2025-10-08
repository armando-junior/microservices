<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * Stock Depleted Event
 * 
 * Disparado quando o estoque de um produto se esgota (quantidade = 0).
 */
final class StockDepleted implements DomainEvent
{
    public function __construct(
        private readonly string $productId,
        private readonly string $productName,
        private readonly DateTimeImmutable $occurredOn = new DateTimeImmutable()
    ) {
    }

    public function eventName(): string
    {
        return 'inventory.stock.depleted';
    }

    public function routingKey(): string
    {
        return 'inventory.stock.depleted';
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

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'event_id' => uniqid('evt_', true),
            'occurred_at' => $this->occurredOn->format('Y-m-d\TH:i:s.uP'),
            'payload' => [
                'product_id' => $this->productId,
                'product_name' => $this->productName,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

