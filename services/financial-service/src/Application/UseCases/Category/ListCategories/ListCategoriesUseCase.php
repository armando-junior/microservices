<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Category\ListCategories;

use Src\Application\DTOs\Category\CategoryOutputDTO;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\ValueObjects\CategoryType;

/**
 * ListCategoriesUseCase
 * 
 * Caso de uso para listagem de categorias financeiras.
 */
final class ListCategoriesUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    /**
     * Executa o caso de uso
     * 
     * @return array<CategoryOutputDTO>
     */
    public function execute(?string $type = null): array
    {
        // Se informado tipo, filtra por tipo
        if ($type) {
            $categories = $this->categoryRepository->findByType(
                CategoryType::fromString($type)
            );
        } else {
            // Senão, retorna todas
            $categories = $this->categoryRepository->findAll();
        }

        return array_map(
            fn($category) => CategoryOutputDTO::fromEntity($category),
            $categories
        );
    }
}


