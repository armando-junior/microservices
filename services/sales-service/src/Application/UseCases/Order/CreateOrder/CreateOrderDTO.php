<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\CreateOrder;

/**
 * Create Order Data Transfer Object
 */
final class CreateOrderDTO
{
    public function __construct(
        public readonly string $customerId,
        public readonly ?string $notes = null
    ) {
    }
}
