<?php

declare(strict_types=1);

namespace Src\Application\DTOs\Supplier;

/**
 * CreateSupplierInputDTO
 * 
 * DTO para criação de fornecedor.
 */
final class CreateSupplierInputDTO
{
    public function __construct(
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
            name: $data['name'],
            document: $data['document'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null
        );
    }
}


