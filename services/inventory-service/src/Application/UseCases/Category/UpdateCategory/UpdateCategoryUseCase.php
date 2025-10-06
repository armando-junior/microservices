<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\UpdateCategory;

use Src\Application\DTOs\CategoryDTO;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryName;

/**
 * Update Category Use Case
 */
final class UpdateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function execute(UpdateCategoryDTO $dto): CategoryDTO
    {
        $categoryId = CategoryId::fromString($dto->id);
        
        $category = $this->categoryRepository->findById($categoryId);
        
        if (!$category) {
            throw new CategoryNotFoundException("Category with ID {$dto->id} not found.");
        }

        // Atualizar campos se fornecidos
        if ($dto->name !== null) {
            $category->updateName(CategoryName::fromString($dto->name));
        }

        if ($dto->description !== null) {
            $category->updateDescription($dto->description);
        }

        if ($dto->status !== null) {
            if ($dto->status === 'active') {
                $category->activate();
            } elseif ($dto->status === 'inactive') {
                $category->deactivate();
            }
        }

        $this->categoryRepository->save($category);

        return new CategoryDTO(
            id: $category->getId()->value(),
            name: $category->getName()->value(),
            slug: $category->getSlug(),
            description: $category->getDescription(),
            status: $category->getStatus(),
            createdAt: $category->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $category->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
