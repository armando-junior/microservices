<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Product\ListProducts;

use Src\Application\DTOs\ProductDTO;
use Src\Domain\Repositories\ProductRepositoryInterface;

/**
 * List Products Use Case
 */
final class ListProductsUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Lista produtos com filtros opcionais
     * 
     * @return array<ProductDTO>
     */
    public function execute(
        ?string $status = null,
        ?string $categoryId = null,
        int $page = 1,
        int $perPage = 15
    ): array {
        $filters = [];
        
        if ($status) {
            $filters['status'] = $status;
        }
        
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        
        $products = $this->productRepository->list($filters, $page, $perPage);
        
        return array_map(
            fn($product) => ProductDTO::fromEntity($product),
            $products
        );
    }
}

