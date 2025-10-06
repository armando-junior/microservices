<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Repositories\CustomerRepositoryInterface;
use Src\Domain\Repositories\OrderRepositoryInterface;
use Src\Infrastructure\Persistence\EloquentCustomerRepository;
use Src\Infrastructure\Persistence\EloquentOrderRepository;

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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
