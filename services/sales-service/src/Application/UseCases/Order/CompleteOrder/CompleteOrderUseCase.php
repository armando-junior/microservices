<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\CompleteOrder;

use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;
use Src\Domain\ValueObjects\OrderStatus;

/**
 * Complete Order Use Case
 * 
 * Marca um pedido como completado (entregue).
 */
final class CompleteOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function execute(array $data): void
    {
        $orderId = OrderId::fromString($data['order_id']);

        // Busca o pedido
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new \DomainException("Order {$orderId->value()} not found");
        }

        // Verifica se pode completar
        if ($order->getStatus()->value() !== 'DELIVERED') {
            throw new \DomainException(
                "Cannot complete order {$orderId->value()} with status {$order->getStatus()->value()}"
            );
        }

        // Marca como completado
        $order->setStatus(OrderStatus::fromString('COMPLETED'));

        // Salva
        $this->orderRepository->save($order);

        // TODO: Publicar evento sales.order.completed
    }
}

