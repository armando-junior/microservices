<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging\RabbitMQ;

use Psr\Log\LoggerInterface;
use Src\Application\UseCases\Stock\ReserveStock\ReserveStockUseCase;
use Src\Application\UseCases\Stock\ReleaseStock\ReleaseStockUseCase;
use Src\Application\UseCases\Stock\CommitReservation\CommitReservationUseCase;

/**
 * Inventory Queue Consumer
 * 
 * Consome eventos da fila inventory.queue:
 * - sales.order.created → Reserva estoque
 * - sales.order.cancelled → Libera estoque reservado
 * - sales.order.confirmed → Confirma reserva (decrementa estoque definitivamente)
 */
final class InventoryQueueConsumer extends BaseRabbitMQConsumer
{
    public function __construct(
        LoggerInterface $logger,
        string $host,
        int $port,
        string $user,
        string $password,
        string $vhost,
        private readonly ReserveStockUseCase $reserveStockUseCase,
        private readonly ReleaseStockUseCase $releaseStockUseCase,
        private readonly CommitReservationUseCase $commitReservationUseCase
    ) {
        parent::__construct($logger, $host, $port, $user, $password, $vhost);
    }

    protected function getQueueName(): string
    {
        return 'inventory.queue';
    }

    protected function handleMessage(array $data): void
    {
        $eventName = $data['event_name'] ?? 'unknown';
        $payload = $data['payload'] ?? [];

        $this->logger->info('Handling event in InventoryQueueConsumer', [
            'event_name' => $eventName,
            'payload' => $payload,
        ]);

        match ($eventName) {
            'sales.order.created' => $this->handleOrderCreated($payload),
            'sales.order.cancelled' => $this->handleOrderCancelled($payload),
            'sales.order.confirmed' => $this->handleOrderConfirmed($payload),
            default => $this->logger->warning('Unhandled event in InventoryQueueConsumer', [
                'event_name' => $eventName,
            ]),
        };
    }

    /**
     * Quando um pedido é criado, reserva o estoque dos itens
     */
    private function handleOrderCreated(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;
        $items = $payload['items'] ?? [];

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        if (empty($items)) {
            $this->logger->warning('Order created without items', [
                'order_id' => $orderId,
            ]);
            return;
        }

        $this->logger->info('Reserving stock for order', [
            'order_id' => $orderId,
            'items_count' => count($items),
        ]);

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;

            if (!$productId || $quantity <= 0) {
                $this->logger->warning('Invalid item in order', [
                    'order_id' => $orderId,
                    'item' => $item,
                ]);
                continue;
            }

            try {
                $this->reserveStockUseCase->execute([
                    'product_id' => $productId,
                    'quantity' => (int) $quantity,
                    'order_id' => $orderId,
                    'reference' => "Order {$orderId}",
                ]);

                $this->logger->info('Stock reserved', [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to reserve stock', [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'error' => $e->getMessage(),
                ]);

                // TODO: Publicar evento inventory.stock.insufficient
                // para que o Sales Service possa cancelar o pedido
                throw $e;
            }
        }
    }

    /**
     * Quando um pedido é cancelado, libera o estoque reservado
     */
    private function handleOrderCancelled(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->info('Releasing stock for cancelled order', [
            'order_id' => $orderId,
        ]);

        try {
            $this->releaseStockUseCase->execute([
                'order_id' => $orderId,
            ]);

            $this->logger->info('Stock released', [
                'order_id' => $orderId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to release stock', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Quando um pedido é confirmado, confirma a reserva (decrementa estoque definitivamente)
     */
    private function handleOrderConfirmed(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->info('Committing stock reservation for confirmed order', [
            'order_id' => $orderId,
        ]);

        try {
            $this->commitReservationUseCase->execute([
                'order_id' => $orderId,
            ]);

            $this->logger->info('Stock reservation committed', [
                'order_id' => $orderId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to commit stock reservation', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

