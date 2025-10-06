<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

use Src\Domain\Entities\OrderItem;

/**
 * Order Item Data Transfer Object
 */
final class OrderItemDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $productId,
        public readonly string $productName,
        public readonly string $sku,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $total,
        public readonly string $createdAt,
        public readonly ?string $updatedAt
    ) {
    }

    /**
     * Cria DTO a partir da entidade
     */
    public static function fromEntity(OrderItem $item): self
    {
        return new self(
            id: $item->getId()->value(),
            productId: $item->getProductId(),
            productName: $item->getProductName(),
            sku: $item->getSku(),
            quantity: $item->getQuantity()->value(),
            unitPrice: $item->getUnitPrice()->value(),
            subtotal: $item->getSubtotal()->value(),
            discount: $item->getDiscount()->value(),
            total: $item->getTotal()->value(),
            createdAt: $item->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $item->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
