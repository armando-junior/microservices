<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Base RabbitMQ Consumer
 * 
 * Classe base para consumidores RabbitMQ usando template pattern.
 * Implementa lógica comum de conexão, consumo, ACK/NACK e tratamento de erros.
 */
abstract class BaseRabbitMQConsumer
{
    protected AMQPStreamConnection $connection;
    protected AMQPChannel $channel;
    protected bool $shouldStop = false;

    public function __construct(
        protected readonly LoggerInterface $logger,
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost
    ) {
    }

    /**
     * Nome da fila que este consumer irá consumir
     */
    abstract protected function getQueueName(): string;

    /**
     * Processa a mensagem recebida
     * 
     * @param array $data Payload da mensagem decodificada
     * @throws \Exception Se houver erro no processamento
     */
    abstract protected function handleMessage(array $data): void;

    /**
     * Conecta ao RabbitMQ
     */
    protected function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost,
                false, // insist
                'AMQPLAIN', // login_method
                null, // login_response
                'en_US', // locale
                10.0, // connection_timeout
                10.0, // read_write_timeout
                null, // context
                true, // keepalive
                60 // heartbeat
            );

            $this->channel = $this->connection->channel();

            $this->logger->info('Consumer connected to RabbitMQ', [
                'queue' => $this->getQueueName(),
                'host' => $this->host,
                'port' => $this->port,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Consumer failed to connect to RabbitMQ', [
                'queue' => $this->getQueueName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Inicia o consumo de mensagens
     * 
     * @param int $prefetchCount Número de mensagens a processar simultaneamente
     */
    public function consume(int $prefetchCount = 1): void
    {
        $this->connect();

        $queue = $this->getQueueName();

        // Configura QoS - quantas mensagens processar por vez
        $this->channel->basic_qos(
            0,              // prefetch_size
            $prefetchCount, // prefetch_count
            false           // global
        );

        $callback = function (AMQPMessage $msg) {
            $this->processMessage($msg);
        };

        // Inicia consumo
        $this->channel->basic_consume(
            $queue,        // queue
            '',            // consumer_tag
            false,         // no_local
            false,         // no_ack (manual ACK)
            false,         // exclusive
            false,         // nowait
            $callback      // callback
        );

        $this->logger->info('Consumer started', [
            'queue' => $queue,
            'prefetch_count' => $prefetchCount,
        ]);

        // Loop de consumo
        while (count($this->channel->callbacks) && !$this->shouldStop) {
            try {
                $this->channel->wait();
            } catch (\Exception $e) {
                $this->logger->error('Error in consumer loop', [
                    'queue' => $queue,
                    'error' => $e->getMessage(),
                ]);
                
                // Reconectar em caso de erro de conexão
                if ($this->isConnectionError($e)) {
                    $this->reconnect();
                }
            }
        }
    }

    /**
     * Processa uma mensagem individual
     */
    protected function processMessage(AMQPMessage $msg): void
    {
        $messageId = $msg->get('message_id') ?? uniqid('msg_', true);
        $startTime = microtime(true);

        try {
            // Decodifica o payload
            $data = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info('Processing message', [
                'queue' => $this->getQueueName(),
                'message_id' => $messageId,
                'event_name' => $data['event_name'] ?? 'unknown',
                'event_id' => $data['event_id'] ?? 'unknown',
            ]);

            // Verifica idempotência (se evento já foi processado)
            if ($this->wasAlreadyProcessed($data)) {
                $this->logger->info('Message already processed (idempotent)', [
                    'queue' => $this->getQueueName(),
                    'event_id' => $data['event_id'] ?? 'unknown',
                ]);

                // ACK mesmo assim (já foi processado)
                $msg->ack();
                return;
            }

            // Processa a mensagem (implementado pela classe filha)
            $this->handleMessage($data);

            // Marca como processado
            $this->markAsProcessed($data);

            // ACK - Confirma processamento bem-sucedido
            $msg->ack();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Message processed successfully', [
                'queue' => $this->getQueueName(),
                'message_id' => $messageId,
                'duration_ms' => $duration,
            ]);

        } catch (\JsonException $e) {
            // Erro de JSON inválido - não faz sentido reprocessar
            $this->logger->error('Invalid JSON in message', [
                'queue' => $this->getQueueName(),
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'body' => substr($msg->body, 0, 500), // Primeiros 500 caracteres
            ]);

            // NACK sem requeue - vai para Dead Letter Queue
            $msg->nack(false, false);

        } catch (\Exception $e) {
            $this->logger->error('Error processing message', [
                'queue' => $this->getQueueName(),
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // NACK com requeue se for erro temporário, sem requeue se for erro permanente
            $requeue = $this->shouldRequeue($e, $msg);

            $msg->nack(false, $requeue);

            if (!$requeue) {
                $this->logger->warning('Message sent to Dead Letter Queue', [
                    'queue' => $this->getQueueName(),
                    'message_id' => $messageId,
                ]);
            }
        }
    }

    /**
     * Verifica se a mensagem já foi processada (idempotência)
     */
    protected function wasAlreadyProcessed(array $data): bool
    {
        // Por padrão, não implementa idempotência
        // Subclasses podem sobrescrever para implementar
        return false;
    }

    /**
     * Marca mensagem como processada (para idempotência)
     */
    protected function markAsProcessed(array $data): void
    {
        // Por padrão, não faz nada
        // Subclasses podem sobrescrever para implementar
    }

    /**
     * Decide se deve recolocar mensagem na fila após erro
     */
    protected function shouldRequeue(\Exception $e, AMQPMessage $msg): bool
    {
        // Verifica número de tentativas (x-death header)
        $deaths = $msg->get('application_headers')?->getNativeData()['x-death'] ?? [];
        $retryCount = 0;

        if (!empty($deaths)) {
            $retryCount = $deaths[0]['count'] ?? 0;
        }

        // Máximo de 3 tentativas
        if ($retryCount >= 3) {
            return false; // Vai para DLQ
        }

        // Não reprocessa erros de validação ou lógica de negócio
        if ($this->isBusinessLogicError($e)) {
            return false;
        }

        // Reprocessa erros temporários (conexão, timeout, etc)
        return true;
    }

    /**
     * Verifica se é erro de lógica de negócio (não deve reprocessar)
     */
    protected function isBusinessLogicError(\Exception $e): bool
    {
        // Adicione aqui suas exceções de negócio
        $businessExceptions = [
            'InvalidArgumentException',
            'DomainException',
            'LogicException',
        ];

        foreach ($businessExceptions as $exceptionClass) {
            if ($e instanceof $exceptionClass || str_contains(get_class($e), $exceptionClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se é erro de conexão
     */
    protected function isConnectionError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        return str_contains($message, 'connection') ||
               str_contains($message, 'broken pipe') ||
               str_contains($message, 'socket') ||
               str_contains($message, 'timeout');
    }

    /**
     * Reconecta ao RabbitMQ
     */
    protected function reconnect(): void
    {
        $this->logger->info('Attempting to reconnect to RabbitMQ');

        try {
            $this->disconnect();
            sleep(5); // Aguarda 5 segundos antes de reconectar
            $this->connect();
        } catch (\Exception $e) {
            $this->logger->error('Failed to reconnect', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Para o consumer gracefully
     */
    public function stop(): void
    {
        $this->logger->info('Stopping consumer', [
            'queue' => $this->getQueueName(),
        ]);

        $this->shouldStop = true;
    }

    /**
     * Desconecta do RabbitMQ
     */
    protected function disconnect(): void
    {
        try {
            if (isset($this->channel)) {
                @$this->channel->close();
            }
        } catch (\Throwable $e) {
            // Ignora erros ao fechar
        }

        try {
            if (isset($this->connection)) {
                @$this->connection->close();
            }
        } catch (\Throwable $e) {
            // Ignora erros ao fechar
        }
    }

    /**
     * Destrutor - garante que conexões sejam fechadas
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}

