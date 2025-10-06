<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

/**
 * RabbitMQ Event Publisher
 * 
 * Publica eventos de domínio no RabbitMQ.
 * 
 * Note: Class is not final to allow mocking in tests
 */
class RabbitMQEventPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private $channel = null;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host', 'rabbitmq'),
                config('rabbitmq.port', 5672),
                config('rabbitmq.user', 'admin'),
                config('rabbitmq.password', 'admin123'),
                config('rabbitmq.vhost', '/')
            );
            
            $this->channel = $this->connection->channel();
            
            // Declarar exchange
            $this->channel->exchange_declare(
                'sales_events',
                'topic',
                false,
                true,  // durable
                false
            );
            
            Log::info('RabbitMQ connection established successfully');
        } catch (\Exception $e) {
            Log::error('Failed to connect to RabbitMQ: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Publica um evento no RabbitMQ
     */
    public function publish(string $eventName, array $payload): void
    {
        try {
            $message = new AMQPMessage(
                json_encode([
                    'event' => $eventName,
                    'payload' => $payload,
                    'timestamp' => now()->toIso8601String(),
                    'service' => 'sales-service',
                ]),
                ['delivery_mode' => 2] // 2 = persistent delivery mode
            );

            $routingKey = 'sales.' . strtolower($eventName);
            
            $this->channel->basic_publish(
                $message,
                'sales_events',
                $routingKey
            );

            Log::info("Event published to RabbitMQ: {$eventName}", [
                'routing_key' => $routingKey,
                'payload' => $payload,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to publish event to RabbitMQ: {$eventName}", [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Publica múltiplos eventos de domínio
     */
    public function publishAll(array $events): void
    {
        foreach ($events as $event) {
            $this->publish(
                $event['event'],
                $event['payload']
            );
        }
    }

    public function __destruct()
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (\Exception $e) {
            Log::warning('Error closing RabbitMQ connection: ' . $e->getMessage());
        }
    }
}
