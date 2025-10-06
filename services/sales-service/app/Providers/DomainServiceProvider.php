<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Infrastructure\Persistence\EloquentProductRepository;
use Src\Infrastructure\Persistence\EloquentCategoryRepository;
use Src\Infrastructure\Persistence\EloquentStockRepository;

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
            ProductRepositoryInterface::class,
            EloquentProductRepository::class
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            EloquentCategoryRepository::class
        );

        $this->app->bind(
            StockRepositoryInterface::class,
            EloquentStockRepository::class
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
