<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

use Exception;

class AccountPayableNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("Account payable with ID {$id} not found");
    }
}


