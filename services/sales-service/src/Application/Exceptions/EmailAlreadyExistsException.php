<?php

declare(strict_types=1);

namespace Src\Application\Exceptions;

class EmailAlreadyExistsException extends ApplicationException
{
    public static function withEmail(string $email): self
    {
        return new self("Email {$email} is already registered");
    }
}
