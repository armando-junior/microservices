<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\GetDepletedStock;

use Src\Application\DTOs\StockDTO;
use Src\Domain\Repositories\StockRepositoryInterface;

/**
 * Get Depleted Stock Use Case
 * 
 * Returns all products with zero stock
 */
final class GetDepletedStockUseCase
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository
    ) {
    }

    /**
     * @return StockDTO[]
     */
    public function execute(): array
    {
        $stocks = $this->stockRepository->findDepleted();

        return array_map(
            fn($stock) => new StockDTO(
                id: $stock->getId()->value(),
                productId: $stock->getProductId()->value(),
                quantity: $stock->getQuantity()->value(),
                minimumQuantity: $stock->getMinimumQuantity()->value(),
                maximumQuantity: $stock->getMaximumQuantity()?->value(),
                isLowStock: $stock->isLowStock(),
                isDepleted: $stock->isDepleted(),
                lastMovementAt: $stock->getLastMovementAt()?->format('Y-m-d H:i:s'),
                createdAt: $stock->getCreatedAt()->format('Y-m-d H:i:s'),
                updatedAt: $stock->getUpdatedAt()?->format('Y-m-d H:i:s')
            ),
            $stocks
        );
    }
}
