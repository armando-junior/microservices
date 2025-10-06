<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\ListCategories;

use Src\Application\DTOs\CategoryDTO;
use Src\Domain\Repositories\CategoryRepositoryInterface;

/**
 * List Categories Use Case
 */
final class ListCategoriesUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return CategoryDTO[]
     */
    public function execute(array $filters = []): array
    {
        $categories = $this->categoryRepository->list($filters);

        return array_map(
            fn($category) => new CategoryDTO(
                id: $category->getId()->value(),
                name: $category->getName()->value(),
                slug: $category->getSlug(),
                description: $category->getDescription(),
                status: $category->getStatus(),
                createdAt: $category->getCreatedAt()->format('Y-m-d H:i:s'),
                updatedAt: $category->getUpdatedAt()?->format('Y-m-d H:i:s')
            ),
            $categories
        );
    }
}
