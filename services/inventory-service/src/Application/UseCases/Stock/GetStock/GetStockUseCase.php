<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\GetStock;

use Src\Application\DTOs\StockDTO;
use Src\Application\Exceptions\StockNotFoundException;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;

/**
 * Get Stock Use Case
 */
final class GetStockUseCase
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository
    ) {
    }

    public function execute(string $productId): StockDTO
    {
        $id = ProductId::fromString($productId);
        
        $stock = $this->stockRepository->findByProductId($id);
        
        if (!$stock) {
            throw StockNotFoundException::forProduct($productId);
        }
        
        return StockDTO::fromEntity($stock);
    }
}

