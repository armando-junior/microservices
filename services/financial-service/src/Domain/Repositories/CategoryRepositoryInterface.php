<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\Category;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryType;

/**
 * CategoryRepositoryInterface
 * 
 * Contrato para persistência de Categorias.
 */
interface CategoryRepositoryInterface
{
    /**
     * Salva uma categoria (create ou update)
     */
    public function save(Category $category): void;

    /**
     * Busca uma categoria por ID
     */
    public function findById(CategoryId $id): ?Category;

    /**
     * Lista todas as categorias
     * 
     * @return array<Category>
     */
    public function findAll(): array;

    /**
     * Lista categorias por tipo (income/expense)
     * 
     * @return array<Category>
     */
    public function findByType(CategoryType $type): array;

    /**
     * Remove uma categoria
     */
    public function delete(CategoryId $id): void;
}


