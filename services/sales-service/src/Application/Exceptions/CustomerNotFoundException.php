<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

class CustomerNotFoundException extends ApplicationException
{
    public static function forId(string $id): self
    {
        return new self("Customer with ID {$id} not found");
    }
}
