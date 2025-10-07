<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\Supplier;
use Src\Domain\ValueObjects\SupplierId;

/**
 * SupplierRepositoryInterface
 * 
 * Contrato para persistência de Fornecedores.
 */
interface SupplierRepositoryInterface
{
    /**
     * Salva um fornecedor (create ou update)
     */
    public function save(Supplier $supplier): void;

    /**
     * Busca um fornecedor por ID
     */
    public function findById(SupplierId $id): ?Supplier;

    /**
     * Busca um fornecedor por documento (CPF/CNPJ)
     */
    public function findByDocument(string $document): ?Supplier;

    /**
     * Lista todos os fornecedores
     * 
     * @return array<Supplier>
     */
    public function findAll(): array;

    /**
     * Lista fornecedores ativos
     * 
     * @return array<Supplier>
     */
    public function findActive(): array;

    /**
     * Lista fornecedores com paginação
     * 
     * @return array{data: array<Supplier>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 15): array;

    /**
     * Remove um fornecedor
     */
    public function delete(SupplierId $id): void;

    /**
     * Verifica se existe um fornecedor com o documento
     */
    public function existsByDocument(string $document): bool;
}


