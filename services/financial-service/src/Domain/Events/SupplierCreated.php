<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use DateTimeImmutable;

/**
 * SupplierCreated Domain Event
 * 
 * Evento disparado quando um fornecedor é criado.
 */
final class SupplierCreated
{
    public function __construct(
        public readonly string $supplierId,
        public readonly string $name,
        public readonly DateTimeImmutable $occurredOn
    ) {
    }

    public function toArray(): array
    {
        return [
            'supplier_id' => $this->supplierId,
            'name' => $this->name,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}


