<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

use Exception;

class SupplierAlreadyExistsException extends Exception
{
    public static function withDocument(string $document): self
    {
        return new self("Supplier with document {$document} already exists");
    }
}


