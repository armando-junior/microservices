<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\DecreaseStock;

use Src\Application\DTOs\StockDTO;
use Src\Application\Exceptions\StockNotFoundException;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;

/**
 * Decrease Stock Use Case
 * 
 * Caso de uso para diminuir estoque (saída).
 */
final class DecreaseStockUseCase
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository
    ) {
    }

    public function execute(DecreaseStockDTO $dto): StockDTO
    {
        // 1. Criar Value Objects
        $productId = ProductId::fromString($dto->productId);
        $quantity = Quantity::fromInt($dto->quantity);

        // 2. Buscar Stock
        $stock = $this->stockRepository->findByProductId($productId);
        
        if (!$stock) {
            throw StockNotFoundException::forProduct($dto->productId);
        }

        // 3. Diminuir estoque (pode lançar InsufficientStockException)
        $stock->decrease($quantity, $dto->reason, $dto->referenceId);

        // 4. Persistir
        $this->stockRepository->save($stock);

        // 5. Salvar movimentações
        $movements = $stock->pullMovements();
        if (!empty($movements)) {
            $this->stockRepository->saveMovements($productId, $movements);
        }

        // 6. Publicar eventos (StockLowAlert, StockDepleted)
        // $events = $stock->pullDomainEvents();
        // $this->eventPublisher->publishAll($events);

        // 7. Retornar DTO
        return StockDTO::fromEntity($stock);
    }
}

