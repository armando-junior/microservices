<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

use Src\Domain\Entities\Order;

/**
 * Order Data Transfer Object
 */
final class OrderDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $orderNumber,
        public readonly string $customerId,
        public readonly string $status,
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $total,
        public readonly string $paymentStatus,
        public readonly ?string $paymentMethod,
        public readonly ?string $notes,
        public readonly ?string $confirmedAt,
        public readonly ?string $cancelledAt,
        public readonly ?string $deliveredAt,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
        public readonly array $items = []
    ) {
    }

    /**
     * Cria DTO a partir da entidade
     */
    public static function fromEntity(Order $order): self
    {
        return new self(
            id: $order->getId()->value(),
            orderNumber: $order->getOrderNumber()->value(),
            customerId: $order->getCustomerId()->value(),
            status: $order->getStatus()->value(),
            subtotal: $order->getSubtotal()->value(),
            discount: $order->getDiscount()->value(),
            total: $order->getTotal()->value(),
            paymentStatus: $order->getPaymentStatus()->value(),
            paymentMethod: $order->getPaymentMethod(),
            notes: $order->getNotes(),
            confirmedAt: $order->getConfirmedAt()?->format('Y-m-d H:i:s'),
            cancelledAt: $order->getCancelledAt()?->format('Y-m-d H:i:s'),
            deliveredAt: $order->getDeliveredAt()?->format('Y-m-d H:i:s'),
            createdAt: $order->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
            items: array_map(
                fn($item) => OrderItemDTO::fromEntity($item),
                $order->getItems()
            )
        );
    }
}
