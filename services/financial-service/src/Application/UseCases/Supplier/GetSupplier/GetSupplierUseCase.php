<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Supplier\GetSupplier;

use Src\Application\DTOs\Supplier\SupplierOutputDTO;
use Src\Application\Exceptions\SupplierNotFoundException;
use Src\Domain\Repositories\SupplierRepositoryInterface;
use Src\Domain\ValueObjects\SupplierId;

/**
 * GetSupplierUseCase
 * 
 * Caso de uso para busca de um fornecedor por ID.
 */
final class GetSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(string $id): SupplierOutputDTO
    {
        $supplier = $this->supplierRepository->findById(
            SupplierId::fromString($id)
        );

        if (!$supplier) {
            throw SupplierNotFoundException::withId($id);
        }

        return SupplierOutputDTO::fromEntity($supplier);
    }
}


