<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\ListOrders;

use Src\Application\DTOs\OrderDTO;
use Src\Domain\Repositories\OrderRepositoryInterface;

/**
 * List Orders Use Case
 * 
 * Lista pedidos com filtros e paginação.
 */
final class ListOrdersUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * Executa o caso de uso
     * 
     * @return OrderDTO[]
     */
    public function execute(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $orders = $this->orderRepository->list($filters, $page, $perPage);
        
        return array_map(
            fn($order) => OrderDTO::fromEntity($order),
            $orders
        );
    }

    /**
     * Retorna o total de pedidos (para paginação)
     */
    public function count(array $filters = []): int
    {
        return $this->orderRepository->count($filters);
    }
}
