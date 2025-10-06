<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

use Src\Domain\Entities\Customer;

/**
 * Customer Data Transfer Object
 */
final class CustomerDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $phoneFormatted,
        public readonly string $document,
        public readonly string $documentFormatted,
        public readonly string $documentType,
        public readonly ?string $addressStreet,
        public readonly ?string $addressNumber,
        public readonly ?string $addressComplement,
        public readonly ?string $addressCity,
        public readonly ?string $addressState,
        public readonly ?string $addressZipCode,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $updatedAt
    ) {
    }

    /**
     * Cria DTO a partir da entidade
     */
    public static function fromEntity(Customer $customer): self
    {
        return new self(
            id: $customer->getId()->value(),
            name: $customer->getName()->value(),
            email: $customer->getEmail()->value(),
            phone: $customer->getPhone()->value(),
            phoneFormatted: $customer->getPhone()->formatted(),
            document: $customer->getDocument()->value(),
            documentFormatted: $customer->getDocument()->formatted(),
            documentType: $customer->getDocument()->type(),
            addressStreet: $customer->getAddressStreet(),
            addressNumber: $customer->getAddressNumber(),
            addressComplement: $customer->getAddressComplement(),
            addressCity: $customer->getAddressCity(),
            addressState: $customer->getAddressState(),
            addressZipCode: $customer->getAddressZipCode(),
            status: $customer->getStatus(),
            createdAt: $customer->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $customer->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
