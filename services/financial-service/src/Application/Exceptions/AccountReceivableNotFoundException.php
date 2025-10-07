<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

use Exception;

class AccountReceivableNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("Account receivable with ID {$id} not found");
    }
}


