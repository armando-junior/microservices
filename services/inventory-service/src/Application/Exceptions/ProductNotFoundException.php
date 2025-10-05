<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

final class ProductNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self("Product not found with ID: {$id}");
    }

    public static function withSKU(string $sku): self
    {
        return new self("Product not found with SKU: {$sku}");
    }
}

