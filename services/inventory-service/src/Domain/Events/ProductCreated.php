<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * Product Created Event
 * 
 * Disparado quando um novo produto é criado no inventário.
 */
final class ProductCreated implements DomainEvent
{
    public function __construct(
        private readonly string $productId,
        private readonly string $name,
        private readonly string $sku,
        private readonly float $price,
        private readonly string $categoryId,
        private readonly int $initialStock,
        private readonly DateTimeImmutable $occurredOn = new DateTimeImmutable()
    ) {
    }

    public function eventName(): string
    {
        return 'inventory.product.created';
    }

    public function routingKey(): string
    {
        return 'inventory.product.created';
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getInitialStock(): int
    {
        return $this->initialStock;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'event_id' => uniqid('evt_', true),
            'occurred_at' => $this->occurredOn->format('Y-m-d\TH:i:s.uP'),
            'payload' => [
                'product_id' => $this->productId,
                'name' => $this->name,
                'sku' => $this->sku,
                'price' => $this->price,
                'category_id' => $this->categoryId,
                'initial_stock' => $this->initialStock,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

