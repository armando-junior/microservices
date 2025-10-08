<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\ConfirmOrder;

use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\OrderDTO;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Domain\Events\OrderConfirmed;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;

/**
 * Confirm Order Use Case
 * 
 * Confirma um pedido, mudando seu status de 'draft' para 'confirmed'.
 * Publica evento OrderConfirmed via RabbitMQ.
 */
final class ConfirmOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(string $orderId): OrderDTO
    {
        // 1. Buscar pedido
        $id = OrderId::fromString($orderId);
        $order = $this->orderRepository->findById($id);
        
        if (!$order) {
            throw OrderNotFoundException::forId($orderId);
        }

        // 2. Confirmar pedido
        $order->confirm();

        // 3. Persistir
        $this->orderRepository->save($order);

        // 4. Publicar evento OrderConfirmed
        $event = new OrderConfirmed(
            orderId: $order->getId()->value(),
            customerId: $order->getCustomerId()->value(),
            totalAmount: $order->getTotalAmount()->value(),
            itemCount: count($order->getItems())
        );
        
        $this->eventPublisher->publish($event);

        // 5. Retornar DTO
        return OrderDTO::fromEntity($order);
    }
}
