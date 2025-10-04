<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\Contracts\TokenGeneratorInterface;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Infrastructure\Auth\JWTTokenGenerator;
use Src\Infrastructure\Messaging\RabbitMQ\RabbitMQEventPublisher;
use Src\Infrastructure\Persistence\Eloquent\EloquentUserRepository;

/**
 * Domain Service Provider
 * 
 * Registra as implementações das interfaces de domínio.
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
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        // Event Publisher binding
        $this->app->singleton(EventPublisherInterface::class, function ($app) {
            return new RabbitMQEventPublisher(
                host: config('rabbitmq.host'),
                port: (int) config('rabbitmq.port'),
                user: config('rabbitmq.user'),
                password: config('rabbitmq.password'),
                vhost: config('rabbitmq.vhost'),
                logger: Log::channel('rabbitmq')
            );
        });

        // Token Generator binding
        $this->app->singleton(TokenGeneratorInterface::class, function ($app) {
            return new JWTTokenGenerator(
                secret: config('jwt.secret'),
                ttl: (int) config('jwt.ttl'),
                issuer: config('jwt.issuer')
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

