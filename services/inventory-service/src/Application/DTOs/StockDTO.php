<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

use Src\Domain\Entities\Stock;

/**
 * Stock Data Transfer Object
 */
final class StockDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $productId,
        public readonly int $quantity,
        public readonly int $minimumQuantity,
        public readonly ?int $maximumQuantity,
        public readonly bool $isLowStock,
        public readonly bool $isDepleted,
        public readonly ?string $lastMovementAt,
        public readonly string $createdAt,
        public readonly ?string $updatedAt
    ) {
    }

    /**
     * Cria um DTO a partir de uma entidade Stock
     */
    public static function fromEntity(Stock $stock): self
    {
        return new self(
            id: $stock->getId()->value(),
            productId: $stock->getProductId()->value(),
            quantity: $stock->getQuantity()->value(),
            minimumQuantity: $stock->getMinimumQuantity()->value(),
            maximumQuantity: $stock->getMaximumQuantity()?->value(),
            isLowStock: $stock->isLowStock(),
            isDepleted: $stock->isDepleted(),
            lastMovementAt: $stock->getLastMovementAt()?->format('Y-m-d H:i:s'),
            createdAt: $stock->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $stock->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'minimum_quantity' => $this->minimumQuantity,
            'maximum_quantity' => $this->maximumQuantity,
            'is_low_stock' => $this->isLowStock,
            'is_depleted' => $this->isDepleted,
            'last_movement_at' => $this->lastMovementAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

