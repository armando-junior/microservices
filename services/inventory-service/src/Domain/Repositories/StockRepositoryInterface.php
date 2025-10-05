<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\Stock;
use Src\Domain\ValueObjects\StockId;
use Src\Domain\ValueObjects\ProductId;

/**
 * Stock Repository Interface
 * 
 * Interface para o repositório de estoque.
 */
interface StockRepositoryInterface
{
    /**
     * Salva um controle de estoque
     */
    public function save(Stock $stock): void;

    /**
     * Busca um estoque por ID
     */
    public function findById(StockId $id): ?Stock;

    /**
     * Busca um estoque por Product ID
     */
    public function findByProductId(ProductId $productId): ?Stock;

    /**
     * Verifica se já existe estoque para o produto
     */
    public function existsForProduct(ProductId $productId): bool;

    /**
     * Lista produtos com estoque baixo
     */
    public function findLowStock(): array;

    /**
     * Lista produtos sem estoque
     */
    public function findDepleted(): array;

    /**
     * Salva movimentações de estoque
     */
    public function saveMovements(ProductId $productId, array $movements): void;

    /**
     * Busca movimentações de um produto
     */
    public function findMovements(ProductId $productId, int $page = 1, int $perPage = 50): array;
}

