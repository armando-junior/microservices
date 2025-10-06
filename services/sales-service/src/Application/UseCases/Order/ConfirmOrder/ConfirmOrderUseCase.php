<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\ConfirmOrder;

use Src\Application\DTOs\OrderDTO;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;
use Src\Infrastructure\Messaging\RabbitMQEventPublisher;

/**
 * Confirm Order Use Case
 * 
 * Confirma um pedido, mudando seu status de 'draft' para 'pending'.
 * Publica evento via RabbitMQ para reservar estoque no Inventory Service.
 */
final class ConfirmOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RabbitMQEventPublisher $eventPublisher
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

        // 4. Publicar evento no RabbitMQ
        $domainEvents = $order->pullDomainEvents();
        $this->eventPublisher->publishAll($domainEvents);

        // 5. Retornar DTO
        return OrderDTO::fromEntity($order);
    }
}
