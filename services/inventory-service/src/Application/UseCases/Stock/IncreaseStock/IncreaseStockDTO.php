<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Stock\IncreaseStock;

final class IncreaseStockDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly int $quantity,
        public readonly string $reason,
        public readonly ?string $referenceId = null
    ) {
    }
}

