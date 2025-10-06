<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\AddOrderItem;

/**
 * Add Order Item DTO
 */
final readonly class AddOrderItemDTO
{
    public function __construct(
        public string $orderId,
        public string $productId,
        public int $quantity,
        public ?float $discount = null
    ) {
    }
}
