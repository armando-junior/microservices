<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Domain\Events\DomainEvent;
use Psr\Log\LoggerInterface;

/**
 * RabbitMQ Event Publisher
 * 
 * Implementação do EventPublisher usando RabbitMQ.
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

            $this->logger->info('Connected to RabbitMQ', [
                'host' => $this->host,
                'port' => $this->port,
                'vhost' => $this->vhost,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to connect to RabbitMQ', [
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

            $this->logger->info('Event published to RabbitMQ', [
                'event' => $event->eventName(),
                'exchange' => $exchange,
                'routing_key' => $event->routingKey(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish event to RabbitMQ', [
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
        // Extrai o prefixo do nome do evento (ex: "auth" de "auth.user.registered")
        $parts = explode('.', $event->eventName());
        $service = $parts[0] ?? 'default';

        return "{$service}.events";
    }

    /**
     * Fecha a conexão com RabbitMQ
     */
    public function __destruct()
    {
        try {
            if (isset($this->channel)) {
                $this->channel->close();
            }
            if (isset($this->connection)) {
                $this->connection->close();
            }
        } catch (\Exception $e) {
            $this->logger->error('Error closing RabbitMQ connection', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

