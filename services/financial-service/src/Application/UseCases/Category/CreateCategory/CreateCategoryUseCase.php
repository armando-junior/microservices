<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\CreateCategory;

use Src\Application\DTOs\Category\CategoryOutputDTO;
use Src\Application\DTOs\Category\CreateCategoryInputDTO;
use Src\Domain\Entities\Category;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryType;

/**
 * CreateCategoryUseCase
 * 
 * Caso de uso para criação de categoria financeira.
 */
final class CreateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(CreateCategoryInputDTO $input): CategoryOutputDTO
    {
        // Cria a categoria
        $category = Category::create(
            name: $input->name,
            type: CategoryType::fromString($input->type),
            description: $input->description
        );

        // Persiste
        $this->categoryRepository->save($category);

        return CategoryOutputDTO::fromEntity($category);
    }
}


