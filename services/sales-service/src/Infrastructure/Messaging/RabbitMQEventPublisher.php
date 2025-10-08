<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Domain\Events\DomainEvent;
use Psr\Log\LoggerInterface;

/**
 * RabbitMQ Event Publisher
 * 
 * Implementação do EventPublisher usando RabbitMQ para o Sales Service.
 */
final class RabbitMQEventPublisher implements EventPublisherInterface
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost,
        private readonly LoggerInterface $logger
    ) {
        $this->connect();
    }

    /**
     * Conecta ao RabbitMQ
     */
    private function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost
            );

            $this->channel = $this->connection->channel();

            $this->logger->info('Sales Service connected to RabbitMQ', [
                'host' => $this->host,
                'port' => $this->port,
                'vhost' => $this->vhost,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Sales Service failed to connect to RabbitMQ', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function publish(DomainEvent $event): void
    {
        try {
            $message = new AMQPMessage(
                $event->toJson(),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => 2, // 2 = persistent
                    'timestamp' => $event->occurredOn()->getTimestamp(),
                ]
            );

            // Determina o exchange baseado no tipo de evento
            $exchange = $this->getExchangeForEvent($event);

            $this->channel->basic_publish(
                $message,
                $exchange,
                $event->routingKey()
            );

            $this->logger->info('Sales Service event published to RabbitMQ', [
                'event' => $event->eventName(),
                'exchange' => $exchange,
                'routing_key' => $event->routingKey(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Sales Service failed to publish event to RabbitMQ', [
                'event' => $event->eventName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function publishBatch(array $events): void
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }

    /**
     * Determina o exchange apropriado para o evento
     */
    private function getExchangeForEvent(DomainEvent $event): string
    {
        // Extrai o prefixo do nome do evento (ex: "sales" de "sales.order.created")
        $parts = explode('.', $event->eventName());
        $service = $parts[0] ?? 'sales';

        return "{$service}.events";
    }

    /**
     * Fecha a conexão com RabbitMQ
     */
    public function __destruct()
    {
        // Silently close connections - errors during destruction are ignored
        try {
            @$this->channel?->close();
        } catch (\Throwable $e) {
            // Ignore
        }

        try {
            @$this->connection?->close();
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}