<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Order\CancelOrder;

/**
 * Cancel Order DTO
 */
final readonly class CancelOrderDTO
{
    public function __construct(
        public string $orderId,
        public ?string $reason = null
    ) {
    }
}
