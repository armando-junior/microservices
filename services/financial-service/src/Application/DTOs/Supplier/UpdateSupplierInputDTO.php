<?php

declare(strict_types=1);

namespace Src\Application\DTOs\Supplier;

/**
 * UpdateSupplierInputDTO
 * 
 * DTO para atualização de fornecedor.
 */
final class UpdateSupplierInputDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $document = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $address = null
    ) {
    }

    /**
     * Cria a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            document: $data['document'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null
        );
    }
}


