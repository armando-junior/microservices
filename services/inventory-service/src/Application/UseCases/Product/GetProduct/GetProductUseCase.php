<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Product\GetProduct;

use Src\Application\DTOs\ProductDTO;
use Src\Application\Exceptions\ProductNotFoundException;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;

/**
 * Get Product Use Case
 */
final class GetProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    public function execute(string $productId): ProductDTO
    {
        $id = ProductId::fromString($productId);
        
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            throw ProductNotFoundException::withId($productId);
        }
        
        return ProductDTO::fromEntity($product);
    }
}

