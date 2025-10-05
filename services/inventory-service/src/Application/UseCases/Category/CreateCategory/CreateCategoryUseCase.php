<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\CreateCategory;

use Src\Application\DTOs\CategoryDTO;
use Src\Domain\Entities\Category;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryName;

/**
 * Create Category Use Case
 */
final class CreateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function execute(CreateCategoryDTO $dto): CategoryDTO
    {
        // 1. Criar Value Objects
        $categoryId = CategoryId::generate();
        $name = CategoryName::fromString($dto->name);

        // 2. Criar entidade Category
        $category = Category::create(
            id: $categoryId,
            name: $name,
            description: $dto->description
        );

        // 3. Persistir
        $this->categoryRepository->save($category);

        // 4. Retornar DTO
        return CategoryDTO::fromEntity($category);
    }
}

