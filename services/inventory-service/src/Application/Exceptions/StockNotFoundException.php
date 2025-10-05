<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

final class StockNotFoundException extends ApplicationException
{
    public static function forProduct(string $productId): self
    {
        return new self("Stock not found for product: {$productId}");
    }
}

