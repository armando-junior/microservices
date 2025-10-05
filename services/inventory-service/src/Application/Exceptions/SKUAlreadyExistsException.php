<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

final class SKUAlreadyExistsException extends ApplicationException
{
    public static function withSKU(string $sku): self
    {
        return new self("SKU already exists: {$sku}");
    }
}

