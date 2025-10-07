<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Supplier\CreateSupplier;

use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\Supplier\CreateSupplierInputDTO;
use Src\Application\DTOs\Supplier\SupplierOutputDTO;
use Src\Application\Exceptions\SupplierAlreadyExistsException;
use Src\Domain\Entities\Supplier;
use Src\Domain\Repositories\SupplierRepositoryInterface;
use Src\Domain\ValueObjects\SupplierName;

/**
 * CreateSupplierUseCase
 * 
 * Caso de uso para criação de fornecedor.
 */
final class CreateSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Executa o caso de uso
     */
    public function execute(CreateSupplierInputDTO $input): SupplierOutputDTO
    {
        // Verifica se já existe fornecedor com o documento
        if ($input->document && $this->supplierRepository->existsByDocument($input->document)) {
            throw SupplierAlreadyExistsException::withDocument($input->document);
        }

        // Cria o fornecedor
        $supplier = Supplier::create(
            name: SupplierName::fromString($input->name),
            document: $input->document,
            email: $input->email,
            phone: $input->phone,
            address: $input->address
        );

        // Persiste
        $this->supplierRepository->save($supplier);

        // Publica eventos de domínio
        $this->eventPublisher->publishAll($supplier->pullDomainEvents());

        return SupplierOutputDTO::fromEntity($supplier);
    }
}


