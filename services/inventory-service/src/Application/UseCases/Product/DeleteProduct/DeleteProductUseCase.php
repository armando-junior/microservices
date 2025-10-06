<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Product\DeleteProduct;

use Src\Application\Exceptions\ProductNotFoundException;
use Src\Application\Exceptions\StockNotFoundException;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;

/**
 * Delete Product Use Case
 */
final class DeleteProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockRepositoryInterface $stockRepository
    ) {
    }

    public function execute(string $productId): void
    {
        $id = ProductId::fromString($productId);
        
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            throw new ProductNotFoundException("Product with ID {$productId} not found.");
        }

        // Verificar se há estoque (não permitir deletar produtos com estoque)
        try {
            $stock = $this->stockRepository->findByProductId($id);
            
            if ($stock && !$stock->isDepleted()) {
                throw new \DomainException("Cannot delete product with stock. Current quantity: {$stock->getQuantity()->value()}");
            }
        } catch (StockNotFoundException $e) {
            // OK, produto sem estoque pode ser deletado
        }

        $this->productRepository->delete($id);
    }
}
