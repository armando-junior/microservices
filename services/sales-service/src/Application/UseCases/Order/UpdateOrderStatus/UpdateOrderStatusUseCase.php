<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\UpdateOrderStatus;

use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;
use Src\Domain\ValueObjects\OrderStatus;

/**
 * Update Order Status Use Case
 * 
 * Atualiza o status de um pedido.
 */
final class UpdateOrderStatusUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function execute(array $data): void
    {
        $orderId = OrderId::fromString($data['order_id']);
        $newStatus = OrderStatus::fromString($data['new_status']);
        $reason = $data['reason'] ?? null;

        // Busca o pedido
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new \DomainException("Order {$orderId->value()} not found");
        }

        // Valida transição de status
        if (!$this->isValidTransition($order->getStatus(), $newStatus)) {
            throw new \DomainException(
                "Invalid status transition from {$order->getStatus()->value()} to {$newStatus->value()}"
            );
        }

        // Atualiza o status
        $order->setStatus($newStatus);

        // Salva
        $this->orderRepository->save($order);

        // TODO: Publicar evento sales.order.status_updated
    }

    private function isValidTransition(OrderStatus $currentStatus, OrderStatus $newStatus): bool
    {
        // Define transições válidas
        $validTransitions = [
            'DRAFT' => ['PENDING', 'CANCELLED'],
            'PENDING' => ['PENDING_PAYMENT', 'CANCELLED'],
            'PENDING_PAYMENT' => ['PAID', 'CANCELLED'],
            'PAID' => ['CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['SHIPPED', 'CANCELLED'],
            'SHIPPED' => ['DELIVERED'],
            'DELIVERED' => ['COMPLETED'],
            'COMPLETED' => [],
            'CANCELLED' => [],
        ];

        $allowed = $validTransitions[$currentStatus->value()] ?? [];
        
        return in_array($newStatus->value(), $allowed, true);
    }
}

