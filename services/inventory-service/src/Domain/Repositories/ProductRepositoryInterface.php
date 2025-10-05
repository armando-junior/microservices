<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\Product;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\SKU;

/**
 * Product Repository Interface
 * 
 * Interface para o repositório de produtos.
 */
interface ProductRepositoryInterface
{
    /**
     * Salva um produto
     */
    public function save(Product $product): void;

    /**
     * Busca um produto por ID
     */
    public function findById(ProductId $id): ?Product;

    /**
     * Busca um produto por SKU
     */
    public function findBySKU(SKU $sku): ?Product;

    /**
     * Verifica se um SKU já existe
     */
    public function existsSKU(SKU $sku): bool;

    /**
     * Deleta um produto
     */
    public function delete(ProductId $id): void;

    /**
     * Lista produtos com paginação e filtros
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Busca produtos por termo
     */
    public function search(string $term, int $page = 1, int $perPage = 15): array;
}

