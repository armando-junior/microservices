<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\ConfirmOrder;

use Src\Application\DTOs\OrderDTO;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;

/**
 * Confirm Order Use Case
 * 
 * Confirma um pedido, mudando seu status de 'draft' para 'pending'.
 * Futuramente, aqui será enviada uma mensagem via RabbitMQ para
 * reservar o estoque no Inventory Service.
 */
final class ConfirmOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
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
            throw OrderNotFoundException::withId($orderId);
        }

        // 2. Confirmar pedido
        $order->confirm();

        // 3. Persistir
        $this->orderRepository->save($order);

        // 4. Publicar evento (RabbitMQ) - TODO: implementar
        // $this->eventPublisher->publish('OrderConfirmed', $order->pullDomainEvents());

        // 5. Retornar DTO
        return OrderDTO::fromEntity($order);
    }
}
