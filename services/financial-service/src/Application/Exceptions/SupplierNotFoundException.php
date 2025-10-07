<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

use Exception;

class SupplierNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("Supplier with ID {$id} not found");
    }

    public static function withDocument(string $document): self
    {
        return new self("Supplier with document {$document} not found");
    }
}


