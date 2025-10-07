<?php

declare(strict_types=1);

namespace Src\Application\DTOs\Supplier;

use DateTimeImmutable;
use Src\Domain\Entities\Supplier;

/**
 * SupplierOutputDTO
 * 
 * DTO para saída de dados de fornecedor.
 */
final class SupplierOutputDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $document,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly bool $active,
        public readonly string $created_at,
        public readonly string $updated_at
    ) {
    }

    /**
     * Cria a partir de uma entidade
     */
    public static function fromEntity(Supplier $supplier): self
    {
        return new self(
            id: $supplier->id()->value(),
            name: $supplier->name()->value(),
            document: $supplier->document(),
            email: $supplier->email(),
            phone: $supplier->phone(),
            address: $supplier->address(),
            active: $supplier->isActive(),
            created_at: $supplier->createdAt()->format('Y-m-d H:i:s'),
            updated_at: $supplier->updatedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document' => $this->document,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


