<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging\RabbitMQ;

use Psr\Log\LoggerInterface;
use Src\Application\UseCases\AccountReceivable\CreateAccountReceivable\CreateAccountReceivableUseCase;

/**
 * Financial Queue Consumer
 * 
 * Consome eventos da fila financial.queue:
 * - sales.order.created → Cria conta a receber
 * - sales.order.confirmed → Atualiza data prevista de recebimento
 * - logistics.shipment.dispatched → Atualiza previsão baseado em envio
 */
final class FinancialQueueConsumer extends BaseRabbitMQConsumer
{
    public function __construct(
        LoggerInterface $logger,
        string $host,
        int $port,
        string $user,
        string $password,
        string $vhost,
        private readonly CreateAccountReceivableUseCase $createAccountReceivableUseCase
    ) {
        parent::__construct($logger, $host, $port, $user, $password, $vhost);
    }

    protected function getQueueName(): string
    {
        return 'financial.queue';
    }

    protected function handleMessage(array $data): void
    {
        $eventName = $data['event_name'] ?? 'unknown';
        $payload = $data['payload'] ?? [];

        $this->logger->info('Handling event in FinancialQueueConsumer', [
            'event_name' => $eventName,
            'payload' => $payload,
        ]);

        match ($eventName) {
            'sales.order.created' => $this->handleOrderCreated($payload),
            'sales.order.confirmed' => $this->handleOrderConfirmed($payload),
            'logistics.shipment.dispatched' => $this->handleShipmentDispatched($payload),
            default => $this->logger->warning('Unhandled event in FinancialQueueConsumer', [
                'event_name' => $eventName,
            ]),
        };
    }

    /**
     * Quando um pedido é criado, cria uma conta a receber
     */
    private function handleOrderCreated(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;
        $customerId = $payload['customer_id'] ?? null;
        $totalAmount = $payload['total_amount'] ?? 0;
        $items = $payload['items'] ?? [];

        if (!$orderId || !$customerId) {
            throw new \InvalidArgumentException('order_id and customer_id are required in payload');
        }

        // Calcula valor total se não foi fornecido
        if ($totalAmount == 0 && !empty($items)) {
            foreach ($items as $item) {
                $totalAmount += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
            }
        }

        $this->logger->info('Creating account receivable for order', [
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'total_amount' => $totalAmount,
        ]);

        try {
            $this->createAccountReceivableUseCase->execute([
                'customer_id' => $customerId,
                'amount' => $totalAmount,
                'due_date' => now()->addDays(30)->format('Y-m-d'), // 30 dias
                'description' => "Pedido #{$orderId}",
                'reference' => $orderId,
            ]);

            $this->logger->info('Account receivable created', [
                'order_id' => $orderId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create account receivable', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Quando um pedido é confirmado, atualiza a previsão de recebimento
     */
    private function handleOrderConfirmed(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->info('Order confirmed - account receivable confirmed', [
            'order_id' => $orderId,
        ]);

        // TODO: Implementar UseCase para atualizar status da conta a receber
        // UpdateAccountReceivableStatusUseCase
    }

    /**
     * Quando um envio é despachado, atualiza previsão baseado em data de entrega
     */
    private function handleShipmentDispatched(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;
        $estimatedDeliveryDate = $payload['estimated_delivery_date'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required in payload');
        }

        $this->logger->info('Shipment dispatched - updating receivable due date', [
            'order_id' => $orderId,
            'estimated_delivery_date' => $estimatedDeliveryDate,
        ]);

        // TODO: Implementar UseCase para atualizar data de vencimento
        // Regra: Vencimento = Data de entrega + 7 dias
    }
}

