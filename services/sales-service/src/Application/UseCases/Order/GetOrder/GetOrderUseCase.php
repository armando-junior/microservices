<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\GetOrder;

use Src\Application\DTOs\OrderDTO;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Domain\ValueObjects\OrderId;

/**
 * Get Order Use Case
 * 
 * Caso de uso para buscar um pedido por ID.
 */
final class GetOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function execute(string $id): OrderDTO
    {
        $orderId = OrderId::fromString($id);

        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw OrderNotFoundException::forId($id);
        }

        return OrderDTO::fromEntity($order);
    }
}
