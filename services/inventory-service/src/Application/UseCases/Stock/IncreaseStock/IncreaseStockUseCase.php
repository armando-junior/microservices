<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\IncreaseStock;

use Src\Application\DTOs\StockDTO;
use Src\Application\Exceptions\StockNotFoundException;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;

/**
 * Increase Stock Use Case
 * 
 * Caso de uso para aumentar estoque (entrada).
 */
final class IncreaseStockUseCase
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository
    ) {
    }

    public function execute(IncreaseStockDTO $dto): StockDTO
    {
        // 1. Criar Value Objects
        $productId = ProductId::fromString($dto->productId);
        $quantity = Quantity::fromInt($dto->quantity);

        // 2. Buscar Stock
        $stock = $this->stockRepository->findByProductId($productId);
        
        if (!$stock) {
            throw StockNotFoundException::forProduct($dto->productId);
        }

        // 3. Aumentar estoque
        $stock->increase($quantity, $dto->reason, $dto->referenceId);

        // 4. Persistir
        $this->stockRepository->save($stock);

        // 5. Salvar movimentações
        $movements = $stock->pullMovements();
        if (!empty($movements)) {
            $this->stockRepository->saveMovements($productId, $movements);
        }

        // 6. Publicar eventos (será feito no repository)
        // $events = $stock->pullDomainEvents();
        // $this->eventPublisher->publishAll($events);

        // 7. Retornar DTO
        return StockDTO::fromEntity($stock);
    }
}

