<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

class ProductNotFoundException extends ApplicationException
{
    public static function forId(string $id): self
    {
        return new self("Product with ID {$id} not found in Inventory Service");
    }
}
