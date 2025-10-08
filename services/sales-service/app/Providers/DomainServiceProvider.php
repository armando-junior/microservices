<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Domain\Repositories\CustomerRepositoryInterface;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Infrastructure\Persistence\EloquentCustomerRepository;
use Src\Infrastructure\Persistence\EloquentOrderRepository;
use Src\Infrastructure\Messaging\RabbitMQEventPublisher;

/**
 * Domain Service Provider
 * 
 * Registra os bindings das interfaces do domínio
 * com suas implementações de infraestrutura.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            CustomerRepositoryInterface::class,
            EloquentCustomerRepository::class
        );

        $this->app->bind(
            OrderRepositoryInterface::class,
            EloquentOrderRepository::class
        );

        // Event Publisher binding
        $this->app->singleton(EventPublisherInterface::class, function ($app) {
            return new RabbitMQEventPublisher(
                host: config('rabbitmq.host'),
                port: (int) config('rabbitmq.port'),
                user: config('rabbitmq.user'),
                password: config('rabbitmq.password'),
                vhost: config('rabbitmq.vhost'),
                logger: $app->make(LoggerInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
