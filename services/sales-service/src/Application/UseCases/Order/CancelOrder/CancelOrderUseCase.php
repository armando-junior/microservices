<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\CancelOrder;

use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\OrderDTO;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Domain\Events\OrderCancelled;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;

/**
 * Cancel Order Use Case
 * 
 * Cancela um pedido e publica evento OrderCancelled.
 */
final class CancelOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(CancelOrderDTO $dto): OrderDTO
    {
        // 1. Buscar pedido
        $id = OrderId::fromString($dto->orderId);
        $order = $this->orderRepository->findById($id);
        
        if (!$order) {
            throw OrderNotFoundException::forId($dto->orderId);
        }

        // 2. Cancelar pedido
        $reason = $dto->reason ?? 'Customer cancellation';
        $order->cancel($reason);

        // 3. Persistir
        $this->orderRepository->save($order);

        // 4. Publicar evento OrderCancelled
        $event = new OrderCancelled(
            orderId: $order->getId()->value(),
            customerId: $order->getCustomerId()->value(),
            reason: $reason
        );
        
        $this->eventPublisher->publish($event);

        // 5. Retornar DTO
        return OrderDTO::fromEntity($order);
    }
}
