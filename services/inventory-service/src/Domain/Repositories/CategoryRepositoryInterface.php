<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\Category;
use Src\Domain\ValueObjects\CategoryId;

/**
 * Category Repository Interface
 * 
 * Interface para o repositório de categorias.
 */
interface CategoryRepositoryInterface
{
    /**
     * Salva uma categoria
     */
    public function save(Category $category): void;

    /**
     * Busca uma categoria por ID
     */
    public function findById(CategoryId $id): ?Category;

    /**
     * Busca uma categoria por slug
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Verifica se um slug já existe
     */
    public function existsSlug(string $slug): bool;

    /**
     * Deleta uma categoria
     */
    public function delete(CategoryId $id): void;

    /**
     * Lista todas as categorias
     */
    public function list(array $filters = []): array;

    /**
     * Conta quantos produtos estão nesta categoria
     */
    public function countProducts(CategoryId $id): int;
}

