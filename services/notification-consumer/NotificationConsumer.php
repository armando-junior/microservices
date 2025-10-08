<?php

/**
 * Notification Consumer (Standalone)
 * 
 * Consumer simples para processar notificações.
 * Pode ser executado como script standalone ou integrado em um microserviço.
 * 
 * Uso:
 *   php NotificationConsumer.php
 */

require __DIR__ . '/../../vendor/autoload.php'; // Ajustar path conforme necessário

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class NotificationConsumer
{
    private AMQPStreamConnection $connection;
    private $channel;
    private bool $shouldStop = false;

    public function __construct(
        private readonly string $host = 'rabbitmq',
        private readonly int $port = 5672,
        private readonly string $user = 'admin',
        private readonly string $password = 'admin123',
        private readonly string $vhost = '/'
    ) {
        $this->connect();
    }

    private function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost,
            false,
            'AMQPLAIN',
            null,
            'en_US',
            10.0,
            10.0,
            null,
            true,
            60
        );

        $this->channel = $this->connection->channel();
        $this->log("✅ Connected to RabbitMQ");
    }

    public function consume(): void
    {
        $queue = 'notification.queue';

        // Configura QoS
        $this->channel->basic_qos(0, 1, false);

        $callback = function (AMQPMessage $msg) {
            $this->processMessage($msg);
        };

        $this->channel->basic_consume(
            $queue,
            '',
            false, // no_local
            false, // no_ack (manual ACK)
            false, // exclusive
            false, // nowait
            $callback
        );

        $this->log("🚀 Consumer started. Waiting for messages from '{$queue}'...");
        $this->log("Press Ctrl+C to stop.");

        // Loop de consumo
        while (count($this->channel->callbacks) && !$this->shouldStop) {
            try {
                $this->channel->wait();
            } catch (\Exception $e) {
                $this->log("❌ Error in consumer loop: " . $e->getMessage());
                
                if ($this->isConnectionError($e)) {
                    $this->log("🔄 Reconnecting...");
                    $this->reconnect();
                }
            }
        }
    }

    private function processMessage(AMQPMessage $msg): void
    {
        $startTime = microtime(true);

        try {
            // Decodifica JSON
            $data = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);

            $eventName = $data['event_name'] ?? 'unknown';
            $eventId = $data['event_id'] ?? 'unknown';

            $this->log("📨 Processing: {$eventName} (ID: {$eventId})");

            // Processa baseado no tipo de evento
            $this->handleEvent($eventName, $data['payload'] ?? []);

            // ACK - confirma processamento
            $msg->ack();

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->log("✅ Processed successfully ({$duration}ms)");

        } catch (\JsonException $e) {
            $this->log("❌ Invalid JSON: " . $e->getMessage());
            $msg->nack(false, false); // Vai para DLQ
        } catch (\Exception $e) {
            $this->log("❌ Error processing message: " . $e->getMessage());
            $msg->nack(false, false); // Vai para DLQ
        }
    }

    private function handleEvent(string $eventName, array $payload): void
    {
        match ($eventName) {
            'auth.user.registered' => $this->sendWelcomeEmail($payload),
            'sales.order.created' => $this->sendOrderConfirmation($payload),
            'sales.order.confirmed' => $this->sendOrderConfirmed($payload),
            'sales.order.cancelled' => $this->sendOrderCancelled($payload),
            'financial.payment.approved' => $this->sendPaymentApproved($payload),
            'financial.payment.failed' => $this->sendPaymentFailed($payload),
            'logistics.shipment.dispatched' => $this->sendShipmentDispatched($payload),
            'logistics.shipment.delivered' => $this->sendShipmentDelivered($payload),
            default => $this->log("⚠️  Unhandled event: {$eventName}"),
        };
    }

    private function sendWelcomeEmail(array $payload): void
    {
        $email = $payload['email'] ?? 'unknown';
        $name = $payload['name'] ?? 'User';
        
        $this->log("📧 Sending welcome email to: {$name} <{$email}>");
        
        // TODO: Integrar com serviço de email (Mailgun, SendGrid, etc)
        // mail($email, 'Welcome!', "Hello {$name}!");
    }

    private function sendOrderConfirmation(array $payload): void
    {
        $orderId = $payload['order_id'] ?? 'unknown';
        
        $this->log("📧 Sending order confirmation for: #{$orderId}");
        
        // TODO: Enviar email de confirmação de pedido
    }

    private function sendOrderConfirmed(array $payload): void
    {
        $orderId = $payload['order_id'] ?? 'unknown';
        
        $this->log("📧 Sending order confirmed notification for: #{$orderId}");
    }

    private function sendOrderCancelled(array $payload): void
    {
        $orderId = $payload['order_id'] ?? 'unknown';
        
        $this->log("📧 Sending order cancelled notification for: #{$orderId}");
    }

    private function sendPaymentApproved(array $payload): void
    {
        $orderId = $payload['order_id'] ?? 'unknown';
        
        $this->log("📧 Sending payment approved notification for: #{$orderId}");
    }

    private function sendPaymentFailed(array $payload): void
    {
        $orderId = $payload['order_id'] ?? 'unknown';
        
        $this->log("📧 Sending payment failed notification for: #{$orderId}");
    }

    private function sendShipmentDispatched(array $payload): void
    {
        $orderId = $payload['order_id'] ?? 'unknown';
        $trackingCode = $payload['tracking_code'] ?? 'N/A';
        
        $this->log("📧 Sending shipment dispatched notification for: #{$orderId} (Tracking: {$trackingCode})");
    }

    private function sendShipmentDelivered(array $payload): void
    {
        $orderId = $payload['order_id'] ?? 'unknown';
        
        $this->log("📧 Sending shipment delivered notification for: #{$orderId}");
    }

    private function isConnectionError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        return str_contains($message, 'connection') ||
               str_contains($message, 'broken pipe') ||
               str_contains($message, 'socket');
    }

    private function reconnect(): void
    {
        try {
            @$this->channel?->close();
            @$this->connection?->close();
            
            sleep(5);
            $this->connect();
        } catch (\Exception $e) {
            $this->log("❌ Failed to reconnect: " . $e->getMessage());
            exit(1);
        }
    }

    public function stop(): void
    {
        $this->shouldStop = true;
        $this->log("🛑 Stopping consumer...");
    }

    private function log(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
    }

    public function __destruct()
    {
        try {
            @$this->channel?->close();
            @$this->connection?->close();
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}

// Registra signal handlers
if (extension_loaded('pcntl')) {
    $consumer = new NotificationConsumer(
        host: getenv('RABBITMQ_HOST') ?: 'rabbitmq',
        port: (int) (getenv('RABBITMQ_PORT') ?: 5672),
        user: getenv('RABBITMQ_USER') ?: 'admin',
        password: getenv('RABBITMQ_PASSWORD') ?: 'admin123',
        vhost: getenv('RABBITMQ_VHOST') ?: '/'
    );

    pcntl_signal(SIGTERM, fn() => $consumer->stop());
    pcntl_signal(SIGINT, fn() => $consumer->stop());

    try {
        $consumer->consume();
    } catch (\Exception $e) {
        echo "❌ Fatal error: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "❌ pcntl extension not loaded. Install it for graceful shutdown.\n";
    
    $consumer = new NotificationConsumer();
    $consumer->consume();
}

