<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\Order;
use Src\Domain\ValueObjects\OrderId;
use Src\Domain\ValueObjects\OrderNumber;
use Src\Domain\ValueObjects\CustomerId;

/**
 * Order Repository Interface
 * 
 * Define o contrato para persistência de pedidos.
 */
interface OrderRepositoryInterface
{
    /**
     * Salva um pedido (create ou update)
     */
    public function save(Order $order): void;

    /**
     * Busca pedido por ID (com itens)
     */
    public function findById(OrderId $id): ?Order;

    /**
     * Busca pedido por número
     */
    public function findByOrderNumber(OrderNumber $orderNumber): ?Order;

    /**
     * Verifica se número de pedido já existe
     */
    public function existsOrderNumber(OrderNumber $orderNumber): bool;

    /**
     * Gera próximo número de pedido
     */
    public function nextOrderNumber(): OrderNumber;

    /**
     * Lista pedidos de um cliente
     * 
     * @return Order[]
     */
    public function findByCustomerId(CustomerId $customerId, int $page = 1, int $perPage = 15): array;

    /**
     * Lista pedidos com filtros
     * 
     * @param array $filters ['status' => 'confirmed', 'customer_id' => 'uuid', 'payment_status' => 'paid']
     * @return Order[]
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Conta total de pedidos
     */
    public function count(array $filters = []): int;

    /**
     * Deleta um pedido
     */
    public function delete(OrderId $id): void;
}
