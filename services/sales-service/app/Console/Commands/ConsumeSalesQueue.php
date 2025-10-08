<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Src\Application\UseCases\Order\CompleteOrder\CompleteOrderUseCase;
use Src\Application\UseCases\Order\UpdateOrderStatus\UpdateOrderStatusUseCase;
use Src\Infrastructure\Messaging\RabbitMQ\SalesQueueConsumer;

/**
 * Consume Sales Queue Command
 * 
 * Comando Artisan para consumir a fila sales.queue
 * 
 * Uso:
 *   php artisan rabbitmq:consume-sales
 *   php artisan rabbitmq:consume-sales --prefetch=5
 */
class ConsumeSalesQueue extends Command
{
    protected $signature = 'rabbitmq:consume-sales
                            {--prefetch=1 : Number of messages to prefetch}
                            {--timeout=0 : Maximum execution time in seconds (0 = unlimited)}';

    protected $description = 'Consume messages from sales.queue';

    public function handle(
        LoggerInterface $logger,
        UpdateOrderStatusUseCase $updateOrderStatusUseCase,
        CompleteOrderUseCase $completeOrderUseCase
    ): int {
        $prefetch = (int) $this->option('prefetch');
        $timeout = (int) $this->option('timeout');

        $this->info("Starting Sales Queue Consumer...");
        $this->info("Prefetch count: {$prefetch}");
        
        if ($timeout > 0) {
            $this->info("Timeout: {$timeout} seconds");
        }

        try {
            $consumer = new SalesQueueConsumer(
                logger: $logger,
                host: config('rabbitmq.host'),
                port: (int) config('rabbitmq.port'),
                user: config('rabbitmq.user'),
                password: config('rabbitmq.password'),
                vhost: config('rabbitmq.vhost'),
                updateOrderStatusUseCase: $updateOrderStatusUseCase,
                completeOrderUseCase: $completeOrderUseCase
            );

            // Registra signal handlers para parada graceful
            if (extension_loaded('pcntl')) {
                pcntl_signal(SIGTERM, function () use ($consumer) {
                    $this->warn('Received SIGTERM, stopping consumer...');
                    $consumer->stop();
                });

                pcntl_signal(SIGINT, function () use ($consumer) {
                    $this->warn('Received SIGINT, stopping consumer...');
                    $consumer->stop();
                });
            }

            if ($timeout > 0) {
                pcntl_alarm($timeout);
                pcntl_signal(SIGALRM, function () use ($consumer) {
                    $this->warn('Timeout reached, stopping consumer...');
                    $consumer->stop();
                });
            }

            $this->info("✅ Consumer started successfully. Waiting for messages...");
            $this->info("Press Ctrl+C to stop.");

            $consumer->consume($prefetch);

            $this->info("Consumer stopped.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to start consumer: {$e->getMessage()}");
            $logger->error('Consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}

