# Comunicação e Mensageria

## Visão Geral

Este documento detalha as estratégias de comunicação entre os microserviços, incluindo comunicação síncrona (REST) e assíncrona (RabbitMQ).

## RabbitMQ - Message Broker

### Arquitetura

```
┌─────────────────────────────────────────────────────────┐
│                     RabbitMQ Server                     │
│                                                         │
│  ┌────────────────────────────────────────────────────┐ │
│  │              Exchanges (Topic)                     │ │
│  ├────────────────────────────────────────────────────┤ │
│  │  auth.events                                       │ │
│  │  inventory.events                                  │ │
│  │  sales.events                                      │ │
│  │  logistics.events                                  │ │
│  │  financial.events                                  │ │
│  └────────────────┬───────────────────────────────────┘ │
│                   │                                      │
│  ┌────────────────▼───────────────────────────────────┐ │
│  │              Queues                                 │ │
│  ├────────────────────────────────────────────────────┤ │
│  │  auth.queue                                        │ │
│  │  inventory.queue                                   │ │
│  │  sales.queue                                       │ │
│  │  logistics.queue                                   │ │
│  │  financial.queue                                   │ │
│  │  notification.queue                                │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Configuração de Exchanges

#### Exchange: auth.events

```php
// config/rabbitmq.php
'exchanges' => [
    'auth.events' => [
        'type' => 'topic',
        'passive' => false,
        'durable' => true,
        'auto_delete' => false,
    ],
],
```

**Routing Keys:**
- `auth.user.registered`
- `auth.user.updated`
- `auth.user.deleted`
- `auth.user.activated`
- `auth.user.deactivated`

#### Exchange: inventory.events

**Routing Keys:**
- `inventory.product.created`
- `inventory.product.updated`
- `inventory.product.deleted`
- `inventory.stock.reserved`
- `inventory.stock.released`
- `inventory.stock.confirmed`
- `inventory.stock.low_alert`

#### Exchange: sales.events

**Routing Keys:**
- `sales.order.created`
- `sales.order.confirmed`
- `sales.order.cancelled`
- `sales.order.completed`
- `sales.customer.created`
- `sales.customer.updated`

#### Exchange: logistics.events

**Routing Keys:**
- `logistics.shipment.created`
- `logistics.shipment.dispatched`
- `logistics.shipment.in_transit`
- `logistics.shipment.out_for_delivery`
- `logistics.shipment.delivered`
- `logistics.shipment.failed`

#### Exchange: financial.events

**Routing Keys:**
- `financial.payment.requested`
- `financial.payment.processed`
- `financial.payment.approved`
- `financial.payment.failed`
- `financial.payment.refunded`
- `financial.invoice.generated`
- `financial.invoice.cancelled`

### Configuração de Queues

```php
'queues' => [
    'inventory.queue' => [
        'passive' => false,
        'durable' => true,
        'exclusive' => false,
        'auto_delete' => false,
        'bindings' => [
            ['exchange' => 'sales.events', 'routing_key' => 'sales.order.*'],
        ],
    ],
    'sales.queue' => [
        'passive' => false,
        'durable' => true,
        'exclusive' => false,
        'auto_delete' => false,
        'bindings' => [
            ['exchange' => 'inventory.events', 'routing_key' => 'inventory.stock.*'],
            ['exchange' => 'financial.events', 'routing_key' => 'financial.payment.*'],
            ['exchange' => 'logistics.events', 'routing_key' => 'logistics.shipment.delivered'],
        ],
    ],
    'notification.queue' => [
        'passive' => false,
        'durable' => true,
        'exclusive' => false,
        'auto_delete' => false,
        'bindings' => [
            ['exchange' => 'auth.events', 'routing_key' => 'auth.user.registered'],
            ['exchange' => 'sales.events', 'routing_key' => 'sales.order.*'],
            ['exchange' => 'logistics.events', 'routing_key' => 'logistics.shipment.*'],
            ['exchange' => 'financial.events', 'routing_key' => 'financial.payment.*'],
        ],
    ],
],
```

### Dead Letter Queues (DLQ)

```php
'queues' => [
    'inventory.queue' => [
        'arguments' => [
            'x-dead-letter-exchange' => 'dlx',
            'x-dead-letter-routing-key' => 'inventory.dlq',
        ],
    ],
],

'dlx' => [
    'type' => 'direct',
    'passive' => false,
    'durable' => true,
    'auto_delete' => false,
],
```

## Event Publisher

### Implementação

```php
<?php

namespace App\Infrastructure\Messaging\Publishers;

use App\Domain\Events\DomainEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class EventPublisher
{
    private AMQPStreamConnection $connection;
    private $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost')
        );
        
        $this->channel = $this->connection->channel();
    }

    public function publish(DomainEvent $event): void
    {
        $exchange = $this->getExchangeForEvent($event);
        $routingKey = $event->getEventName();
        
        $message = new AMQPMessage(
            json_encode($event->toArray()),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp' => time(),
                'message_id' => $event->getEventId(),
            ]
        );

        $this->channel->basic_publish($message, $exchange, $routingKey);

        \Log::info("Event published: {$routingKey}", [
            'event_id' => $event->getEventId(),
            'exchange' => $exchange,
        ]);
    }

    private function getExchangeForEvent(DomainEvent $event): string
    {
        $eventName = $event->getEventName();
        [$domain, $entity, $action] = explode('.', $eventName);
        
        return "{$domain}.events";
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
```

## Event Consumer

### Implementação Base

```php
<?php

namespace App\Infrastructure\Messaging\Consumers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class BaseConsumer
{
    private AMQPStreamConnection $connection;
    protected $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost')
        );
        
        $this->channel = $this->connection->channel();
    }

    abstract protected function getQueueName(): string;
    abstract protected function handleMessage(array $data): void;

    public function consume(): void
    {
        $queue = $this->getQueueName();

        $callback = function (AMQPMessage $msg) {
            try {
                $data = json_decode($msg->body, true);

                \Log::info("Processing message", [
                    'queue' => $this->getQueueName(),
                    'event' => $data['event_name'] ?? 'unknown',
                    'event_id' => $data['event_id'] ?? 'unknown',
                ]);

                $this->handleMessage($data);

                $msg->ack();

                \Log::info("Message processed successfully");

            } catch (\Exception $e) {
                \Log::error("Error processing message: {$e->getMessage()}", [
                    'exception' => $e,
                    'message' => $msg->body,
                ]);

                // Rejeitar e enviar para DLQ
                $msg->nack(false, false);
            }
        };

        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
```

### Exemplo de Consumer Específico

```php
<?php

namespace App\Infrastructure\Messaging\Consumers;

class InventoryConsumer extends BaseConsumer
{
    public function __construct(
        private ReserveStockUseCase $reserveStockUseCase,
        private ReleaseStockUseCase $releaseStockUseCase
    ) {
        parent::__construct();
    }

    protected function getQueueName(): string
    {
        return 'inventory.queue';
    }

    protected function handleMessage(array $data): void
    {
        $eventName = $data['event_name'];
        $payload = $data['payload'];

        match ($eventName) {
            'sales.order.created' => $this->handleOrderCreated($payload),
            'sales.order.cancelled' => $this->handleOrderCancelled($payload),
            default => \Log::warning("Unhandled event: {$eventName}"),
        };
    }

    private function handleOrderCreated(array $payload): void
    {
        foreach ($payload['items'] as $item) {
            $this->reserveStockUseCase->execute([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'order_id' => $payload['order_id'],
            ]);
        }
    }

    private function handleOrderCancelled(array $payload): void
    {
        foreach ($payload['items'] as $item) {
            $this->releaseStockUseCase->execute([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'order_id' => $payload['order_id'],
            ]);
        }
    }
}
```

## Laravel Queue Workers

### Configuração

```php
// config/queue.php
'connections' => [
    'rabbitmq' => [
        'driver' => 'rabbitmq',
        'queue' => env('RABBITMQ_QUEUE', 'default'),
        'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,
        'hosts' => [
            [
                'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                'port' => env('RABBITMQ_PORT', 5672),
                'user' => env('RABBITMQ_USER', 'guest'),
                'password' => env('RABBITMQ_PASSWORD', 'guest'),
                'vhost' => env('RABBITMQ_VHOST', '/'),
            ],
        ],
        'options' => [
            'ssl_options' => [
                'cafile' => env('RABBITMQ_SSL_CAFILE', null),
                'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
                'local_key' => env('RABBITMQ_SSL_LOCALKEY', null),
                'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
            ],
            'queue' => [
                'job' => VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob::class,
            ],
        ],
    ],
],
```

### Executar Worker

```bash
# Development
php artisan queue:work rabbitmq --queue=inventory.queue --tries=3 --timeout=90

# Production (com Supervisor)
php artisan queue:work rabbitmq --queue=inventory.queue --tries=3 --timeout=90 --sleep=3 --daemon
```

### Supervisor Config

```ini
[program:inventory-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/inventory-service/artisan queue:work rabbitmq --queue=inventory.queue --tries=3 --timeout=90 --sleep=3
autostart=true
autorestart=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/inventory-service/storage/logs/worker.log
```

## Comunicação REST

### HTTP Client (Guzzle)

```php
<?php

namespace App\Infrastructure\External\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function get(string $url, array $options = []): array
    {
        try {
            $response = $this->client->get($url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            \Log::error("HTTP GET Error: {$e->getMessage()}", [
                'url' => $url,
                'options' => $options,
            ]);
            throw $e;
        }
    }

    public function post(string $url, array $data = [], array $options = []): array
    {
        try {
            $options['json'] = $data;
            $response = $this->client->post($url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            \Log::error("HTTP POST Error: {$e->getMessage()}", [
                'url' => $url,
                'data' => $data,
            ]);
            throw $e;
        }
    }
}
```

## Padrões de Mensagens

### Event Envelope

```json
{
  "event_id": "evt_1234567890",
  "event_name": "sales.order.created",
  "event_version": "1.0",
  "occurred_at": "2025-10-04T10:30:00Z",
  "correlation_id": "corr_0987654321",
  "causation_id": "evt_previous_event_id",
  "payload": {
    "order_id": "ORD-2025-001",
    "customer_id": "CUST-456",
    "total": 150.00,
    "items": [...]
  },
  "metadata": {
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "ip_address": "192.168.1.100",
    "service": "sales-service",
    "version": "1.0.0"
  }
}
```

### Command Message

```json
{
  "command_id": "cmd_1234567890",
  "command_name": "reserve_stock",
  "issued_at": "2025-10-04T10:30:00Z",
  "payload": {
    "product_id": "PROD-123",
    "quantity": 5,
    "order_id": "ORD-2025-001"
  }
}
```

## Idempotência

### Strategy

```php
<?php

namespace App\Infrastructure\Messaging;

class IdempotencyGuard
{
    private $redis;

    public function __construct()
    {
        $this->redis = app('redis');
    }

    public function isProcessed(string $eventId): bool
    {
        return $this->redis->exists("processed_event:{$eventId}");
    }

    public function markAsProcessed(string $eventId, int $ttl = 86400): void
    {
        $this->redis->setex("processed_event:{$eventId}", $ttl, true);
    }
}
```

### Usage in Consumer

```php
protected function handleMessage(array $data): void
{
    $eventId = $data['event_id'];

    if ($this->idempotencyGuard->isProcessed($eventId)) {
        \Log::info("Event already processed, skipping", ['event_id' => $eventId]);
        return;
    }

    // Process message...

    $this->idempotencyGuard->markAsProcessed($eventId);
}
```

## Monitoring

### RabbitMQ Management

- **URL:** http://localhost:15672
- **Métricas:**
  - Mensagens por segundo
  - Tamanho das filas
  - Taxa de consumo
  - Mensagens não confirmadas

### Prometheus Metrics

```php
// Custom metrics
$registry = new CollectorRegistry(new Redis());

$queueSize = $registry->getOrRegisterGauge(
    'rabbitmq',
    'queue_size',
    'Size of RabbitMQ queue',
    ['queue']
);

$queueSize->set(100, ['inventory.queue']);
```

---

**Próximo:** [Resiliência e Segurança](../05-resilience/README.md)

