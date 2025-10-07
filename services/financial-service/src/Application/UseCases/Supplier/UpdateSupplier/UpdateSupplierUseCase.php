<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Supplier\UpdateSupplier;

use Src\Application\DTOs\Supplier\SupplierOutputDTO;
use Src\Application\DTOs\Supplier\UpdateSupplierInputDTO;
use Src\Application\Exceptions\SupplierNotFoundException;
use Src\Domain\Repositories\SupplierRepositoryInterface;
use Src\Domain\ValueObjects\SupplierId;
use Src\Domain\ValueObjects\SupplierName;

/**
 * UpdateSupplierUseCase
 * 
 * Caso de uso para atualização de fornecedor.
 */
final class UpdateSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(UpdateSupplierInputDTO $input): SupplierOutputDTO
    {
        // Busca o fornecedor
        $supplier = $this->supplierRepository->findById(
            SupplierId::fromString($input->id)
        );

        if (!$supplier) {
            throw SupplierNotFoundException::withId($input->id);
        }

        // Atualiza informações
        $supplier->update(
            name: SupplierName::fromString($input->name),
            document: $input->document,
            email: $input->email,
            phone: $input->phone,
            address: $input->address
        );

        // Persiste
        $this->supplierRepository->save($supplier);

        return SupplierOutputDTO::fromEntity($supplier);
    }
}


