<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\CancelOrder;

use Src\Application\DTOs\OrderDTO;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;

/**
 * Cancel Order Use Case
 * 
 * Cancela um pedido e libera estoque (via RabbitMQ).
 */
final class CancelOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
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
            throw OrderNotFoundException::withId($dto->orderId);
        }

        // 2. Cancelar pedido
        $reason = $dto->reason ?? 'Customer cancellation';
        $order->cancel($reason);

        // 3. Persistir
        $this->orderRepository->save($order);

        // 4. Publicar evento para liberar estoque (RabbitMQ) - TODO: implementar
        // $this->eventPublisher->publish('OrderCancelled', $order->pullDomainEvents());

        // 5. Retornar DTO
        return OrderDTO::fromEntity($order);
    }
}
