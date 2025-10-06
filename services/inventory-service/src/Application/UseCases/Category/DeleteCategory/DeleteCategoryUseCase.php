<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\DeleteCategory;

use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;

/**
 * Delete Category Use Case
 */
final class DeleteCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function execute(string $categoryId): void
    {
        $id = CategoryId::fromString($categoryId);
        
        $category = $this->categoryRepository->findById($id);
        
        if (!$category) {
            throw new CategoryNotFoundException("Category with ID {$categoryId} not found.");
        }

        // Verificar se há produtos associados
        $productCount = $this->categoryRepository->countProducts($id);
        
        if ($productCount > 0) {
            throw new \DomainException("Cannot delete category with {$productCount} associated products.");
        }

        $this->categoryRepository->delete($id);
    }
}
