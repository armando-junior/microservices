<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\Contracts\UnitOfWorkInterface;
use Src\Domain\Repositories\AccountPayableRepositoryInterface;
use Src\Domain\Repositories\AccountReceivableRepositoryInterface;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\Repositories\SupplierRepositoryInterface;
use Src\Infrastructure\Messaging\RabbitMQEventPublisher;
use Src\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountPayableRepository;
use Src\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountReceivableRepository;
use Src\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use Src\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;
use Src\Infrastructure\Persistence\UnitOfWork;

/**
 * FinancialServiceProvider
 * 
 * Registra os bindings da camada de infraestrutura.
 */
class FinancialServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(
            SupplierRepositoryInterface::class,
            EloquentSupplierRepository::class
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            EloquentCategoryRepository::class
        );

        $this->app->bind(
            AccountPayableRepositoryInterface::class,
            EloquentAccountPayableRepository::class
        );

        $this->app->bind(
            AccountReceivableRepositoryInterface::class,
            EloquentAccountReceivableRepository::class
        );

        // Infrastructure Contracts
        $this->app->singleton(
            EventPublisherInterface::class,
            RabbitMQEventPublisher::class
        );

        $this->app->singleton(
            UnitOfWorkInterface::class,
            UnitOfWork::class
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


