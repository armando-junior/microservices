<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Src\Application\Contracts\EventPublisherInterface;

/**
 * RabbitMQEventPublisher
 * 
 * Implementação do publicador de eventos usando RabbitMQ.
 */
class RabbitMQEventPublisher implements EventPublisherInterface
{
    private const EXCHANGE = 'financial_events';

    private AMQPStreamConnection $connection;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            host: config('rabbitmq.host', 'rabbitmq'),
            port: config('rabbitmq.port', 5672),
            user: config('rabbitmq.user', 'admin'),
            password: config('rabbitmq.password', 'admin123'),
            vhost: config('rabbitmq.vhost', '/')
        );
    }

    public function publish(object $event): void
    {
        $channel = $this->connection->channel();

        // Declara exchange do tipo fanout
        $channel->exchange_declare(
            self::EXCHANGE,
            'fanout',
            false,
            true,
            false
        );

        // Prepara mensagem
        $eventName = $this->getEventName($event);
        $payload = $this->serializeEvent($event);

        $message = new AMQPMessage(
            $payload,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp' => time(),
                'type' => $eventName,
            ]
        );

        // Publica mensagem
        $channel->basic_publish($message, self::EXCHANGE);

        $channel->close();
    }

    public function publishAll(array $events): void
    {
        if (empty($events)) {
            return;
        }

        foreach ($events as $event) {
            $this->publish($event);
        }
    }

    /**
     * Obtém o nome do evento
     */
    private function getEventName(object $event): string
    {
        $className = get_class($event);
        return substr($className, strrpos($className, '\\') + 1);
    }

    /**
     * Serializa o evento para JSON
     */
    private function serializeEvent(object $event): string
    {
        if (method_exists($event, 'toArray')) {
            $data = $event->toArray();
        } else {
            $data = get_object_vars($event);
        }

        return json_encode([
            'event' => $this->getEventName($event),
            'data' => $data,
            'service' => 'financial-service',
            'timestamp' => date('Y-m-d H:i:s'),
        ], JSON_THROW_ON_ERROR);
    }

    public function __destruct()
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
    }
}
