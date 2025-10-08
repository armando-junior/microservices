<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging\RabbitMQ;

use Psr\Log\LoggerInterface;
use Src\Application\UseCases\Order\UpdateOrderStatus\UpdateOrderStatusUseCase;
use Src\Application\UseCases\Order\CompleteOrder\CompleteOrderUseCase;

/**
 * Sales Queue Consumer
 * 
 * Consome eventos da fila sales.queue:
 * - inventory.stock.reserved → Atualiza status do pedido
 * - inventory.stock.insufficient → Cancela pedido (sem estoque)
 * - financial.payment.approved → Atualiza status de pagamento
 * - financial.payment.failed → Cancela pedido
 * - logistics.shipment.delivered → Completa pedido
 */
final class SalesQueueConsumer extends BaseRabbitMQConsumer
{
    public function __construct(
        LoggerInterface $logger,
        string $host,
        int $port,
        string $user,
        string $password,
        string $vhost,
        private readonly UpdateOrderStatusUseCase $updateOrderStatusUseCase,
        private readonly CompleteOrderUseCase $completeOrderUseCase
    ) {
        parent::__construct($logger, $host, $port, $user, $password, $vhost);
    }

    protected function getQueueName(): string
    {
        return 'sales.queue';
    }

    protected function handleMessage(array $data): void
    {
        $eventName = $data['event_name'] ?? 'unknown';
        $payload = $data['payload'] ?? [];

        $this->logger->info('Handling event in SalesQueueConsumer', [
            'event_name' => $eventName,
            'payload' => $payload,
        ]);

        match ($eventName) {
            'inventory.stock.reserved' => $this->handleStockReserved($payload),
            'inventory.stock.insufficient' => $this->handleStockInsufficient($payload),
            'inventory.stock.depleted' => $this->handleStockDepleted($payload),
            'financial.payment.approved' => $this->handlePaymentApproved($payload),
            'financial.payment.failed' => $this->handlePaymentFailed($payload),
            'logistics.shipment.delivered' => $this->handleShipmentDelivered($payload),
            default => $this->logger->warning('Unhandled event in SalesQueueConsumer', [
                'event_name' => $eventName,
            ]),
        };
    }

    private function handleStockReserved(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->info('Stock reserved for order', [
            'order_id' => $orderId,
        ]);

        // Atualiza status do pedido para "PENDING_PAYMENT"
        $this->updateOrderStatusUseCase->execute([
            'order_id' => $orderId,
            'new_status' => 'PENDING_PAYMENT',
            'reason' => 'Stock reserved successfully',
        ]);
    }

    private function handleStockInsufficient(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->warning('Insufficient stock for order', [
            'order_id' => $orderId,
        ]);

        // Cancela o pedido
        $this->updateOrderStatusUseCase->execute([
            'order_id' => $orderId,
            'new_status' => 'CANCELLED',
            'reason' => 'Insufficient stock',
        ]);
    }

    private function handleStockDepleted(array $payload): void
    {
        $productId = $payload['product_id'] ?? null;

        $this->logger->warning('Stock depleted for product', [
            'product_id' => $productId,
        ]);

        // TODO: Notificar pedidos pendentes afetados por este produto
    }

    private function handlePaymentApproved(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->info('Payment approved for order', [
            'order_id' => $orderId,
        ]);

        // Atualiza status do pedido para "PAID"
        $this->updateOrderStatusUseCase->execute([
            'order_id' => $orderId,
            'new_status' => 'PAID',
            'reason' => 'Payment approved',
        ]);
    }

    private function handlePaymentFailed(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->warning('Payment failed for order', [
            'order_id' => $orderId,
        ]);

        // Cancela o pedido e libera estoque
        $this->updateOrderStatusUseCase->execute([
            'order_id' => $orderId,
            'new_status' => 'CANCELLED',
            'reason' => 'Payment failed',
        ]);

        // TODO: Publicar evento para liberar estoque reservado
    }

    private function handleShipmentDelivered(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->info('Shipment delivered for order', [
            'order_id' => $orderId,
        ]);

        // Completa o pedido
        $this->completeOrderUseCase->execute([
            'order_id' => $orderId,
        ]);
    }
}

