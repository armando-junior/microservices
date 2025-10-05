<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\GetCategory;

use Src\Application\DTOs\CategoryDTO;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;

/**
 * Get Category Use Case
 */
final class GetCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function execute(string $categoryId): CategoryDTO
    {
        $id = CategoryId::fromString($categoryId);
        
        $category = $this->categoryRepository->findById($id);
        
        if (!$category) {
            throw CategoryNotFoundException::withId($categoryId);
        }
        
        return CategoryDTO::fromEntity($category);
    }
}

