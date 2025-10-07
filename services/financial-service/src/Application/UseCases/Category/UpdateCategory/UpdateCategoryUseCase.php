<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\UpdateCategory;

use Src\Application\DTOs\Category\CategoryOutputDTO;
use Src\Application\DTOs\Category\UpdateCategoryInputDTO;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;

/**
 * UpdateCategoryUseCase
 * 
 * Caso de uso para atualização de categoria financeira.
 */
final class UpdateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(UpdateCategoryInputDTO $input): CategoryOutputDTO
    {
        // Busca a categoria
        $category = $this->categoryRepository->findById(
            CategoryId::fromString($input->id)
        );

        if (!$category) {
            throw CategoryNotFoundException::withId($input->id);
        }

        // Atualiza informações
        $category->update(
            name: $input->name,
            description: $input->description
        );

        // Persiste
        $this->categoryRepository->save($category);

        return CategoryOutputDTO::fromEntity($category);
    }
}


