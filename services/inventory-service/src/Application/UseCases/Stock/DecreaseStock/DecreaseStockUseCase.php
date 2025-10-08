<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\DecreaseStock;

use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\StockDTO;
use Src\Application\Exceptions\StockNotFoundException;
use Src\Application\Exceptions\ProductNotFoundException;
use Src\Domain\Events\StockLowAlert;
use Src\Domain\Events\StockDepleted;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;

/**
 * Decrease Stock Use Case
 * 
 * Caso de uso para diminuir estoque (saída) e publicar eventos de alerta.
 */
final class DecreaseStockUseCase
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly EventPublisherInterface $eventPublisher
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

        // 3. Buscar Product (para obter o nome)
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw ProductNotFoundException::withId($dto->productId);
        }

        // 4. Diminuir estoque (pode lançar InsufficientStockException)
        $stock->decrease($quantity, $dto->reason, $dto->referenceId);

        // 5. Persistir
        $this->stockRepository->save($stock);

        // 6. Salvar movimentações
        $movements = $stock->pullMovements();
        if (!empty($movements)) {
            $this->stockRepository->saveMovements($productId, $movements);
        }

        // 7. Publicar eventos de alerta
        $currentStock = $stock->getQuantity()->value();
        $minimumStock = $stock->getMinimumQuantity()->value();

        // Estoque esgotado
        if ($currentStock === 0) {
            $event = new StockDepleted(
                productId: $productId->value(),
                productName: $product->getName()->value()
            );
            $this->eventPublisher->publish($event);
        }
        // Estoque baixo (mas não zerado)
        elseif ($currentStock <= $minimumStock) {
            $event = new StockLowAlert(
                productId: $productId->value(),
                productName: $product->getName()->value(),
                currentStock: $currentStock,
                minimumStock: $minimumStock
            );
            $this->eventPublisher->publish($event);
        }

        // 8. Retornar DTO
        return StockDTO::fromEntity($stock);
    }
}

