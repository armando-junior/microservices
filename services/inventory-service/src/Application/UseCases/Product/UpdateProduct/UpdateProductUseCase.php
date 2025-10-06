<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Product\UpdateProduct;

use Src\Application\DTOs\ProductDTO;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Application\Exceptions\ProductNotFoundException;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\Price;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\ProductName;

/**
 * Update Product Use Case
 */
final class UpdateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function execute(UpdateProductDTO $dto): ProductDTO
    {
        $productId = ProductId::fromString($dto->id);
        
        $product = $this->productRepository->findById($productId);
        
        if (!$product) {
            throw new ProductNotFoundException("Product with ID {$dto->id} not found.");
        }

        // Validar categoria se fornecida
        if ($dto->categoryId !== null) {
            $categoryId = CategoryId::fromString($dto->categoryId);
            $category = $this->categoryRepository->findById($categoryId);
            
            if (!$category) {
                throw new CategoryNotFoundException("Category with ID {$dto->categoryId} not found.");
            }
        }

        // Atualizar campos se fornecidos
        if ($dto->name !== null) {
            $product->updateName(ProductName::fromString($dto->name));
        }

        if ($dto->price !== null) {
            $product->updatePrice(Price::fromFloat($dto->price));
        }

        if ($dto->categoryId !== null) {
            $product->updateCategory(CategoryId::fromString($dto->categoryId));
        }

        if ($dto->barcode !== null) {
            $product->updateBarcode($dto->barcode);
        }

        if ($dto->description !== null) {
            $product->updateDescription($dto->description);
        }

        if ($dto->status !== null) {
            if ($dto->status === 'active') {
                $product->activate();
            } elseif ($dto->status === 'inactive') {
                $product->deactivate();
            }
        }

        $this->productRepository->save($product);

        return new ProductDTO(
            id: $product->getId()->value(),
            name: $product->getName()->value(),
            sku: $product->getSku()->value(),
            price: $product->getPrice()->value(),
            categoryId: $product->getCategoryId()?->value(),
            barcode: $product->getBarcode(),
            description: $product->getDescription(),
            status: $product->getStatus(),
            createdAt: $product->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $product->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
